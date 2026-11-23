<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpamGuardTest extends TestCase
{
    use RefreshDatabase;

    private function booking($service, array $extra = []): array
    {
        return array_merge(['catalog_item_id' => $service->id, 'date' => '2026-11-16', 'time' => '10:00', 'name' => 'Ana Cruz', 'email' => 'ana@example.test'], $extra);
    }

    public function test_forms_carry_a_hidden_honeypot_and_bots_that_fill_it_are_refused(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        foreach (['public.book', 'public.order', 'public.quote', 'public.request', 'public.track'] as $page) {
            $this->get(route($page, $b))->assertOk()->assertSee('name="company_website"', false)->assertDontSee('cf-turnstile', false);
        }
        $this->get(route('register'))->assertSee('name="company_website"', false);

        $this->post(route('public.book.review', $b), $this->booking($s, ['company_website' => 'http://spam.example']))->assertSessionHasErrors('human');
        $this->post(route('public.book.store', $b), $this->booking($s, ['company_website' => 'x', 'idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']))->assertSessionHasErrors('human');
        $this->post(route('register'), ['name' => 'Bot', 'email' => 'bot@example.test', 'password' => 'password-123456', 'password_confirmation' => 'password-123456', 'company_website' => 'x'])->assertSessionHasErrors('human');
        $this->assertSame(0, Appointment::count());
        $this->assertFalse(User::where('email', 'bot@example.test')->exists());

        // People leave it empty and go through.
        $this->post(route('public.book.review', $b), $this->booking($s, ['company_website' => '']))->assertOk()->assertSee('Review and confirm');
    }

    public function test_turnstile_is_checked_once_per_visit_when_configured(): void
    {
        config(['services.turnstile.site_key' => '0x4AAAAAAAtest', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => false, 'error-codes' => ['invalid-input-response']])
            ->push(['success' => true])]);
        $b = $this->makeBusiness();
        $s = $this->service($b);

        $page = $this->get(route('public.book', $b))->assertOk()->assertSee('data-sitekey="0x4AAAAAAAtest"', false);
        $this->assertStringContainsString('https://challenges.cloudflare.com', $page->headers->get('Content-Security-Policy'));

        $this->post(route('public.book.review', $b), $this->booking($s))->assertSessionHasErrors('human'); // no token
        $this->post(route('public.book.review', $b), $this->booking($s, ['cf-turnstile-response' => 'bad']))->assertSessionHasErrors('human');
        $this->post(route('public.book.review', $b), $this->booking($s, ['cf-turnstile-response' => 'good']))->assertOk()->assertDontSee('cf-turnstile', false);
        Http::assertSent(fn ($r) => $r['secret'] === 'secret' && $r['response'] === 'good');

        // The confirm step doesn't ask again.
        $this->post(route('public.book.store', $b), $this->booking($s, ['idempotency_key' => (string) Str::uuid(), 'confirmed' => '1']))->assertRedirect();
        $this->assertSame(1, Appointment::count());
        Http::assertSentCount(2);
    }

    public function test_cloudflare_outage_does_not_block_customers(): void
    {
        config(['services.turnstile.site_key' => 'k', 'services.turnstile.secret_key' => 's']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->post(route('public.book.review', $b), $this->booking($s, ['cf-turnstile-response' => 'tok']))->assertOk();
    }
}
