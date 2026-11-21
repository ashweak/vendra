<?php

namespace Tests\Feature;

use App\Http\Middleware\DemoLock;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_send_security_headers_and_hsts_only_over_https(): void
    {
        $r = $this->get('/');
        $r->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertStringContainsString("object-src 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("connect-src 'self'", $r->headers->get('Content-Security-Policy'));
        $r->assertHeaderMissing('Strict-Transport-Security');

        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_public_demo_blocks_changes_that_affect_other_visitors(): void
    {
        $b = $this->makeBusiness(['name' => 'Original Name']);
        $owner = $this->addMember($b, 'owner');
        config(['vendra.public_demo' => true]);

        $this->actingAs($owner)->from(route('dashboard.settings.profile'))
            ->put(route('dashboard.settings.profile'), ['name' => 'Vandalized', 'accent_color' => '#000000'])
            ->assertRedirect(route('dashboard.settings.profile'))
            ->assertSessionHasErrors(['rule' => DemoLock::MESSAGE]);
        $this->assertSame('Original Name', $b->fresh()->name);

        $this->actingAs($owner)->put(route('dashboard.account.password'), [
            'current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('rule');
        $this->assertTrue(Hash::check('password', $owner->fresh()->password));

        // Reading still works.
        $this->actingAs($owner)->get(route('dashboard.settings.profile'))->assertOk();
    }

    public function test_forgot_password_gives_the_same_answer_and_emails_a_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'owner@example.test']);

        $known = $this->post(route('password.email'), ['email' => 'owner@example.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.test']);
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_sets_a_new_password_once(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);
        $token = Password::createToken($user);
        $data = ['token' => $token, 'email' => 'owner@example.test', 'password' => 'brand-new-pass-1', 'password_confirmation' => 'brand-new-pass-1'];

        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('brand-new-pass-1', $user->fresh()->password));

        $this->post(route('password.update'), ['password' => 'another-pass-22', 'password_confirmation' => 'another-pass-22'] + $data)
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('brand-new-pass-1', $user->fresh()->password));
    }

    public function test_account_password_change_needs_the_current_password(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');

        $this->actingAs($owner)->put(route('dashboard.account.password'), [
            'current_password' => 'wrong-password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($owner)->put(route('dashboard.account.password'), [
            'current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password-123', $owner->fresh()->password));
    }

    public function test_changing_email_needs_password_and_a_new_verification(): void
    {
        Notification::fake();
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');

        $this->actingAs($owner)->put(route('dashboard.account.update'), ['name' => $owner->name, 'email' => 'new@example.test'])
            ->assertSessionHasErrors('current_password');
        $this->assertNotSame('new@example.test', $owner->fresh()->email);

        $this->actingAs($owner)->put(route('dashboard.account.update'), ['name' => $owner->name, 'email' => 'new@example.test', 'current_password' => 'password'])
            ->assertSessionHasNoErrors();
        $owner->refresh();
        $this->assertSame('new@example.test', $owner->email);
        $this->assertFalse($owner->hasVerifiedEmail());
        Notification::assertSentTo($owner, VerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $owner->id, 'hash' => sha1('new@example.test')]);
        $this->actingAs($owner)->get($url)->assertRedirect(route('dashboard.overview'));
        $this->assertTrue($owner->fresh()->hasVerifiedEmail());
    }

    public function test_business_cannot_go_live_before_the_email_is_verified(): void
    {
        $b = $this->makeBusiness(['activated_at' => null]);
        $owner = $this->addMember($b, 'owner');
        $owner->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($owner)->post(route('dashboard.activate'))->assertSessionHasErrors('rule');
        $this->assertNull($b->fresh()->activated_at);
    }
}
