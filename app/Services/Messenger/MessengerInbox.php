<?php

namespace App\Services\Messenger;

use App\Models\Business;
use App\Models\Conversation;
use App\Services\Ai\AssistantService;
use App\Services\ConversationService;
use Illuminate\Support\Facades\Cache;

/**
 * Turns Messenger webhook events into Vendra conversations: the same inbox, auto-assistant,
 * handoff and staff takeover as the website chat. Drafts arrive as a "Review & confirm" button.
 */
class MessengerInbox
{
    public function __construct(
        private ConversationService $conversations,
        private AssistantService $assistant,
        private MessengerClient $client,
    ) {}

    public function handle(array $payload): int
    {
        if (($payload['object'] ?? null) !== 'page') {
            return 0;
        }
        $handled = 0;
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            $business = Business::where('messenger_page_id', (string) ($entry['id'] ?? ''))->where('status', 'active')->first();
            if (! $business || ! $business->messengerConnected()) {
                continue;
            }
            foreach ((array) ($entry['messaging'] ?? []) as $event) {
                $handled += $this->event($business, $event) ? 1 : 0;
            }
        }

        return $handled;
    }

    private function event(Business $b, array $e): bool
    {
        $psid = (string) ($e['sender']['id'] ?? '');
        $msg = $e['message'] ?? null;
        if ($psid === '' || ! is_array($msg) || ! empty($msg['is_echo']) || $psid === $b->messenger_page_id) {
            return false; // our own replies, reads, deliveries
        }
        $mid = (string) ($msg['mid'] ?? '');
        if ($mid !== '' && ! Cache::add('messenger-mid:'.hash('sha256', $mid), 1, now()->addDay())) {
            return false; // Facebook retries the same event; handle it once
        }

        $c = Conversation::forBusiness($b)->where('channel', 'messenger')->where('external_id', $psid)->latest('id')->first();
        if (! $c) {
            [$c] = $this->conversations->start($b);
            $c->forceFill(['channel' => 'messenger', 'external_id' => $psid])->save();
        }

        $text = trim((string) ($msg['text'] ?? ''));
        if ($text === '') {
            // Photos, stickers, voice: shown to staff; the assistant only reads text.
            $this->conversations->addMessage($c, 'customer', '(Sent a photo, sticker or file in Messenger — open the Page inbox to see it.)', meta: ['mid' => $mid]);
            if ($c->fresh()->aiMayReply()) {
                $this->client->sendText($b, $psid, 'Thanks! I can only read text messages. A staff member will check what you sent.');
            }

            return true;
        }

        $this->conversations->addMessage($c, 'customer', mb_substr($text, 0, 2000), meta: ['mid' => $mid]);
        $result = $this->assistant->reply($c->fresh(), $text);
        if ($result['reply']) {
            if (! empty($result['draft']['url'])) {
                $reply = str_replace(['Tap “Review & confirm”', 'I-tap ang “Review & confirm”'], ['Tap the button below', 'I-tap ang button sa ibaba'], $result['reply']);
                $this->client->sendButton($b, $psid, $reply, 'Review & confirm', $result['draft']['url']);
            } else {
                $this->client->sendText($b, $psid, $result['reply']);
            }
        }

        return true;
    }
}
