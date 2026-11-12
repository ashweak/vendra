<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Support\PermissionMatrix;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function booking($b, array $extra = []): Appointment
    {
        return app(AppointmentService::class)->book($b, $this->customer($b, uniqid().'@example.test'),
            array_merge(['catalog_item_id' => $this->service($b)->id, 'start' => '2026-11-16 10:00'], $extra));
    }

    public function test_owner_cannot_read_another_business_records(): void
    {
        $a = $this->makeBusiness();
        $b = $this->makeBusiness();
        $ownerA = $this->addMember($a, 'owner');
        $foreign = $this->booking($b);

        $this->actingAs($ownerA)->get(route('dashboard.appointments.show', $foreign->id))->assertNotFound();
        $this->actingAs($ownerA)->get(route('dashboard.customers.show', $foreign->customer_id))->assertNotFound();
        $this->actingAs($ownerA)->post(route('dashboard.appointments.action', $foreign->id), ['action' => 'cancelled', 'reason' => 'x'])->assertNotFound();
        $this->assertSame('confirmed', $foreign->fresh()->status);
    }

    public function test_switching_to_a_business_you_do_not_belong_to_is_forbidden(): void
    {
        $a = $this->makeBusiness();
        $b = $this->makeBusiness();
        $owner = $this->addMember($a, 'owner');
        $this->actingAs($owner)->post(route('dashboard.switch'), ['business_id' => $b->id])->assertForbidden();
    }

    public function test_staff_permissions_follow_the_matrix(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertOk();
        $this->actingAs($staff)->get(route('dashboard.catalog.index'))->assertOk();
        $this->actingAs($staff)->get(route('dashboard.catalog.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard.settings.profile'))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard.reports'))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard.customers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard.settings.export', 'customers'))->assertForbidden();
        $this->assertFalse(PermissionMatrix::allows('manager', 'quotes.approve'));
        $this->assertTrue(PermissionMatrix::allows('owner', 'quotes.approve'));
    }

    public function test_staff_only_see_assigned_records(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');
        $other = $this->addMember($b, 'staff');
        $this->schedule($b, $staff);
        $this->schedule($b, $other);
        $mine = $this->booking($b, ['staff_id' => $staff->id]);
        $theirs = $this->booking($b, ['staff_id' => $other->id]);
        $theirs->customer->update(['name' => 'Zed Other']);

        $this->actingAs($staff)->get(route('dashboard.appointments.show', $mine->id))->assertOk();
        $this->actingAs($staff)->get(route('dashboard.appointments.show', $theirs->id))->assertForbidden();
        $this->actingAs($staff)->get(route('dashboard.appointments.index', ['date' => '2026-11-16']))
            ->assertSee($mine->service_name)->assertDontSee('Zed Other');
        // Staff cannot approve
        $this->actingAs($staff)->post(route('dashboard.appointments.action', $mine->id), ['action' => 'confirmed'])->assertForbidden();
    }

    public function test_disabled_member_loses_access(): void
    {
        $b = $this->makeBusiness();
        $staff = $this->addMember($b, 'staff');
        $b->memberships()->where('user_id', $staff->id)->update(['is_active' => false]);
        $this->actingAs($staff)->get(route('dashboard.overview'))->assertRedirect(route('onboarding.business'));
    }

    public function test_owner_mfa_is_enforced(): void
    {
        config(['vendra.enforce_mfa' => true]);
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertRedirect(route('mfa.setup'));

        $this->actingAs($owner)->get(route('mfa.setup'))->assertOk();
        $secret = session('mfa_pending_secret');
        $this->actingAs($owner)->post(route('mfa.setup'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->actingAs($owner)->post(route('mfa.setup'), ['code' => Totp::code($secret)])->assertRedirect(route('dashboard.overview'));
        $this->assertTrue($owner->fresh()->hasMfa());
        $this->actingAs($owner)->get(route('dashboard.overview'))->assertOk();

        // Staff are not forced into MFA
        $staff = $this->addMember($b, 'staff');
        $this->actingAs($staff)->withSession([])->get(route('dashboard.overview'))->assertOk();
    }

    public function test_platform_admin_has_no_automatic_business_access(): void
    {
        $b = $this->makeBusiness();
        $admin = \App\Models\User::factory()->create();
        $admin->forceFill(['is_platform_admin' => true])->save();
        $this->actingAs($admin)->get(route('admin.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.show', $b))->assertOk()->assertSee('No active grant');
        $this->actingAs($admin)->get(route('dashboard.overview'))->assertRedirect(route('admin.index'));

        $nonAdmin = $this->addMember($b, 'owner');
        $this->actingAs($nonAdmin)->get(route('admin.index'))->assertForbidden();
    }
}
