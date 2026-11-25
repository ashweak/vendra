<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Order;
use App\Models\User;
use App\Support\Totp;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamSecurityAndVisitsTest extends TestCase
{
    use RefreshDatabase;

    private function withMfa(User $u): User
    {
        $u->forceFill(['mfa_secret' => Totp::generateSecret(), 'mfa_enabled_at' => now()])->save();

        return $u;
    }

    public function test_owner_can_require_two_step_sign_in_for_staff(): void
    {
        config(['vendra.enforce_mfa' => true]);
        $b = $this->makeBusiness();
        $owner = $this->withMfa($this->addMember($b, 'owner'));
        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertOk(); // optional by default

        $this->actingAs($owner)->withSession(['mfa_passed' => true])->get(route('dashboard.team.index'))->assertOk()->assertSee('Sign-in security')->assertSee('Two-step off');
        $this->actingAs($owner)->withSession(['mfa_passed' => true])->post(route('dashboard.team.security'), ['require_staff_mfa' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($b->fresh()->setting('require_staff_mfa'));

        $this->flushSession();
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertRedirect(route('mfa.setup'));
        $this->actingAs($staff)->get(route('mfa.setup'))->assertOk();
        $this->actingAs($staff)->post(route('mfa.setup'), ['code' => Totp::code(session('mfa_pending_secret'))]);
        $this->assertTrue($staff->fresh()->hasMfa());
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertOk();

        // Managers can't change the policy; only owners.
        $manager = $this->addMember($b, 'manager');
        $this->withMfa($manager);
        $this->actingAs($manager)->withSession(['mfa_passed' => true])->post(route('dashboard.team.security'), ['require_staff_mfa' => '0'])->assertForbidden();
    }

    public function test_owner_resets_a_lost_phone_but_not_for_people_of_other_businesses(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $staff = $this->withMfa($this->addMember($b, 'staff'));
        $shared = $this->withMfa($this->addMember($b, 'staff'));
        $this->addMember($this->makeBusiness(), 'staff', $shared);
        $m = fn (User $u) => $b->memberships()->where('user_id', $u->id)->value('id');

        $this->actingAs($owner)->get(route('dashboard.team.index'))->assertSee('Reset two-step sign-in (lost phone)');
        $this->actingAs($owner)->post(route('dashboard.team.reset_mfa', $m($staff)))->assertSessionHasNoErrors();
        $this->assertFalse($staff->fresh()->hasMfa());
        $this->actingAs($owner)->post(route('dashboard.team.reset_mfa', $m($shared)))->assertForbidden();
        $this->assertTrue($shared->fresh()->hasMfa());
        $this->actingAs($owner)->post(route('dashboard.team.reset_mfa', $m($owner)))->assertForbidden();
    }

    public function test_anyone_who_turned_two_step_on_is_always_asked_for_a_code(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->withMfa($this->addMember($b, 'staff'));
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertRedirect(route('mfa.challenge'));
        $this->actingAs($staff)->withSession(['mfa_passed' => true])->get(route('dashboard.overview'))->assertOk();
    }

    public function test_returning_customers_are_marked_with_their_visit_count(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $ana = $this->customer($b, 'ana@example.test');
        $ben = $this->customer($b, 'ben@example.test');
        $ben->update(['name' => 'Ben']);
        $mk = function ($c, string $when, string $status) use ($b) {
            $start = CarbonImmutable::parse($when, 'Asia/Manila')->utc();
            $a = new Appointment(['customer_id' => $c->id, 'service_name' => 'Haircut', 'price_cents' => 25000, 'starts_at' => $start, 'ends_at' => $start->addHour(), 'status' => $status]);
            $a->business_id = $b->id;
            $a->save();

            return $a;
        };
        $mk($ana, '2026-10-01 10:00', 'completed');
        $mk($ana, '2026-10-20 10:00', 'completed');
        $mk($ana, '2026-11-01 10:00', 'cancelled');
        $o = new Order(['customer_id' => $ana->id, 'status' => 'completed', 'fulfillment_method' => 'pickup', 'subtotal_cents' => 1000, 'total_cents' => 1000]);
        $o->business_id = $b->id;
        $o->save();
        $today = $mk($ana, '2026-11-16 15:00', 'confirmed');
        $mk($ben, '2026-11-16 16:00', 'confirmed');

        $html = $this->actingAs($owner)->get(route('dashboard.appointments.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'Returning · 3 visits'));
        $this->actingAs($owner)->get(route('dashboard.appointments.show', $today->id))->assertSee('Returning · 3 visits');

        // A finished visit doesn't count itself.
        $today->update(['status' => 'completed']);
        $this->actingAs($owner)->get(route('dashboard.appointments.show', $today->id))->assertSee('Returning · 3 visits');

        $this->actingAs($owner)->get(route('dashboard.customers.index', ['returning' => 1]))->assertSee('ana@example.test')->assertDontSee('ben@example.test')
            ->assertSee('Returning · 4 visits');
    }
}
