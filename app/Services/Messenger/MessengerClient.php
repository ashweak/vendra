<?php

namespace App\Services\Messenger;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Sends messages to a customer through the business's Facebook Page (Messenger Send API). */
class MessengerClient
{
    public static function enabled(): bool
    {
        return filled(config('services.messenger.app_secret')) && filled(config('services.messenger.verify_token'));
    }

    private function url(string $path): string
    {
        return 'https://graph.facebook.com/'.config('services.messenger.graph_version', 'v21.0').'/'.$path;
    }

    /** Returns null on success, or a short reason it couldn't be delivered. */
    public function sendText(Business $b, string $psid, string $text): ?string
    {
        return $this->send($b, $psid, ['text' => Str::limit($text, 1990, '…')]);
    }

    /** A message with a "Review & confirm" button that opens the prepared form. */
    public function sendButton(Business $b, string $psid, string $text, string $title, string $url): ?string
    {
        return $this->send($b, $psid, ['attachment' => ['type' => 'template', 'payload' => [
            'template_type' => 'button',
            'text' => Str::limit($text, 630, '…'),
            'buttons' => [['type' => 'web_url', 'url' => $url, 'title' => Str::limit($title, 20, '')]],
        ]]]);
    }

    private function send(Business $b, string $psid, array $message): ?string
    {
        if (! $b->messengerConnected()) {
            return 'Messenger is not connected.';
        }
        try {
            $res = Http::timeout(10)->withToken($b->messenger_token)->post($this->url('me/messages'), [
                'recipient' => ['id' => $psid],
                'messaging_type' => 'RESPONSE',
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Messenger send failed: '.$e->getMessage(), ['business' => $b->id]);

            return 'Messenger could not be reached. Try again.';
        }
        if ($res->successful()) {
            return null;
        }
        $code = (int) $res->json('error.code');
        $sub = (int) $res->json('error.error_subcode');
        Log::warning('Messenger rejected a message', ['business' => $b->id, 'status' => $res->status(), 'code' => $code, 'subcode' => $sub]);

        return match (true) {
            $code === 10 || $sub === 2018278 => 'It’s been more than 24 hours since the customer’s last Messenger message, so Facebook won’t deliver a reply. Ask them to message the Page again.',
            $code === 190 => 'The Page access token expired or was revoked. Reconnect Messenger in Settings.',
            default => 'Facebook didn’t accept the message ('.($res->json('error.message') ? Str::limit((string) $res->json('error.message'), 120) : 'HTTP '.$res->status()).').',
        };
    }

    /** Checks a Page token: returns the Page's id and name, or null. */
    public function page(string $token): ?array
    {
        try {
            $res = Http::timeout(10)->withToken($token)->get($this->url('me'), ['fields' => 'id,name']);
        } catch (\Throwable) {
            return null;
        }

        return $res->successful() && $res->json('id') ? ['id' => (string) $res->json('id'), 'name' => (string) $res->json('name')] : null;
    }
}
