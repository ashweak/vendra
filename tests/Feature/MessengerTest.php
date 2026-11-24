<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessengerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.messenger.app_secret' => 'app-secret', 'services.messenger.verify_token' => 'verify-me']);
    }

    private function connected(): Business
    {
        $b = $this->makeBusiness();
        $b->forceFill(['messenger_page_id' => '1234567890', 'messenger_token' => 'EAAG-page-token-for-tests-0001'])->save();

        return $b;
    }

    private function deliver(array $payload, ?string $secret = 'app-secret')
    {
        $raw = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($secret) {
            $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256='.hash_hmac('sha256', $raw, $secret);
        }

        return $this->call('POST', '/webhooks/messenger', [], [], [], $headers, $raw);
    }

    private function message(string $text, string $mid = 'm1', string $psid = '555000111', string $page = '1234567890'): array
    {
        return ['object' => 'page', 'entry' => [['id' => $page, 'messaging' => [[
            'sender' => ['id' => $psid], 'recipient' => ['id' => $page], 'timestamp' => 1, 'message' => ['mid' => $mid, 'text' => $text],
        ]]]]];
    }

    public function test_webhook_verification_and_signature(): void
    {
        $this->get('/webhooks/messenger?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=abc123')->assertOk()->assertSeeText('abc123');
        $this->get('/webhooks/messenger?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123')->assertForbidden();

        Http::fake();
        $this->connected();
        $this->deliver($this->message('Magkano haircut?'), secret: null)->assertForbidden();
        $this->deliver($this->message('Magkano haircut?'), secret: 'someone-else')->assertForbidden();
        $this->assertSame(0, Message::count());
        Http::assertNothingSent();

        config(['services.messenger.app_secret' => null]);
        $this->deliver($this->message('hi'))->assertNotFound();
    }

    public function test_messages_reach_the_inbox_and_the_assistant_replies_in_messenger(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['recipient_id' => '555000111', 'message_id' => 'x'])]);
        $b = $this->connected();
        $this->service($b, ['name' => 'Haircut', 'price_cents' => 25000]);

        $this->deliver($this->message('Magkano po ang haircut?'))->assertOk()->assertSeeText('EVENT_RECEIVED');
        $this->deliver($this->message('Magkano po ang haircut?'))->assertOk(); // Facebook retry: handled once
        $c = Conversation::sole();
        $this->assertSame('messenger', $c->channel);
        $this->assertSame('555000111', $c->external_id);
        $this->assertSame(1, $c->messages()->where('sender_type', 'customer')->count());

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->url() === 'https://graph.facebook.com/v21.0/me/messages'
            && $r->hasHeader('Authorization', 'Bearer EAAG-page-token-for-tests-0001')
            && $r['recipient']['id'] === '555000111' && $r['messaging_type'] === 'RESPONSE'
            && str_contains($r['message']['text'], '₱250.00'));

        // A booking draft comes with a Review & confirm button that opens the prepared form.
        $this->deliver($this->message('Pa-book po ng haircut bukas 3pm', 'm2'))->assertOk();
        Http::assertSent(fn ($r) => ($r['message']['attachment']['payload']['buttons'][0]['title'] ?? null) === 'Review & confirm'
            && str_contains($r['message']['attachment']['payload']['buttons'][0]['url'], '/book?')
            && str_contains($r['message']['attachment']['payload']['text'], 'button sa ibaba'));
        $this->assertSame(1, Conversation::count(), 'same sender, same conversation');

        // Other pages, echoes of our own replies and unknown pages are ignored.
        $echo = $this->message('our reply', 'm3');
        $echo['entry'][0]['messaging'][0]['message']['is_echo'] = true;
        $this->deliver($echo)->assertOk();
        $this->deliver($this->message('hello', 'm4', page: '999'))->assertOk();
        $this->assertSame(2, $c->messages()->where('sender_type', 'customer')->count());
    }

    public function test_staff_replies_are_sent_to_messenger_and_failures_are_shown(): void
    {
        $b = $this->connected();
        $owner = $this->addMember($b, 'owner');
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['message_id' => 'a'])                        // assistant reply
            ->push(['message_id' => 'b'])                        // staff reply delivered
            ->push(['error' => ['code' => 10, 'error_subcode' => 2018278, 'message' => 'Outside window']], 400)]);
        $this->deliver($this->message('I want to talk to a real person'))->assertOk();
        $c = Conversation::sole();
        $this->assertSame('waiting_for_staff', $c->fresh()->state);

        $this->actingAs($owner)->get(route('dashboard.conversations.index'))->assertSee('Messenger visitor #'.$c->id);
        $this->actingAs($owner)->post(route('dashboard.conversations.reply', $c->id), ['body' => 'Hi! This is Lara, how can I help?'])->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => ($r['message']['text'] ?? null) === 'Hi! This is Lara, how can I help?');

        $this->actingAs($owner)->post(route('dashboard.conversations.reply', $c->id), ['body' => 'Are you still there?'])->assertSessionHasErrors('messenger');
        $this->assertTrue($c->messages()->where('is_internal', true)->where('body', 'like', 'Not delivered to Messenger:%24 hours%')->exists());
    }

    public function test_owner_connects_a_page_and_the_token_is_encrypted(): void
    {
        Http::fake(['graph.facebook.com/*/me*' => Http::sequence()->push(['id' => '1234567890', 'name' => 'Lumina Studio'])->push(['id' => '42', 'name' => 'Other Page'])]);
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.settings.messenger'))->assertOk()->assertSee(route('webhooks.messenger'));

        $this->actingAs($owner)->post(route('dashboard.settings.messenger'), ['page_id' => '1234567890', 'token' => 'EAAG-real-looking-page-token-123'])
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'Connected to Lumina Studio. New Messenger chats will appear in your inbox.');
        $this->assertTrue($b->fresh()->messengerConnected());
        $stored = DB::table('businesses')->where('id', $b->id)->value('messenger_token');
        $this->assertStringNotContainsString('EAAG-real-looking-page-token-123', $stored);
        $this->assertArrayNotHasKey('messenger_token', $b->fresh()->toArray());

        // A token for a different Page is refused.
        $other = $this->makeBusiness();
        $owner2 = $this->addMember($other, 'owner');
        $this->actingAs($owner2)->post(route('dashboard.settings.messenger'), ['page_id' => '777777777', 'token' => 'EAAG-another-page-token-456'])->assertSessionHasErrors('token');
        $this->actingAs($owner2)->post(route('dashboard.settings.messenger'), ['page_id' => '1234567890', 'token' => 'EAAG-another-page-token-456'])->assertSessionHasErrors('page_id');

        $this->actingAs($owner)->post(route('dashboard.settings.messenger.disconnect'))->assertSessionHasNoErrors();
        $this->assertFalse($b->fresh()->messengerConnected());
        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->get(route('dashboard.settings.messenger'))->assertForbidden();
    }
}
