<?php

namespace Tests\Feature;

use App\Exceptions\RuleViolation;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(AppointmentService::class);
    }

    public function test_slots_respect_hours_and_existing_bookings(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $slots = $this->svc->availableSlots($b, $s, '2026-11-16');
        $this->assertSame('09:00', $slots[0]['start']->format('H:i'));
        $this->assertSame('17:00', end($slots)['start']->format('H:i'));

        $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
        $times = collect($this->svc->availableSlots($b, $s, '2026-11-16'))->map(fn ($x) => $x['start']->format('H:i'));
        $this->assertNotContains('10:00', $times);
        $this->assertNotContains('09:30', $times); // would overlap 10:00–11:00
        $this->assertContains('11:00', $times);
        $this->assertSame([], $this->svc->availableSlots($b, $s, '2026-11-22')); // Sunday closed
    }

    public function test_double_booking_is_prevented(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
        $this->expectException(RuleViolation::class);
        $this->svc->book($b, $this->customer($b, 'b@example.test'), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:30']);
    }

    public function test_staff_calendars_are_independent(): void
    {
        $b = $this->makeBusiness();
        $joy = $this->addMember($b, 'staff');
        $ken = $this->addMember($b, 'staff');
        $this->schedule($b, $joy);
        $this->schedule($b, $ken);
        $s = $this->service($b);
        $a1 = $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00', 'staff_id' => $joy->id]);
        $a2 = $this->svc->book($b, $this->customer($b, 'x@example.test'), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
        $this->assertSame($ken->id, $a2->staff_id);
        $this->assertSame($joy->id, $a1->assigned_to);
        $this->expectException(RuleViolation::class);
        $this->svc->book($b, $this->customer($b, 'y@example.test'), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
    }

    public function test_idempotent_submission_returns_existing(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $c = $this->customer($b);
        $a = $this->svc->book($b, $c, ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00', 'idempotency_key' => 'k1']);
        $again = $this->svc->book($b, $c, ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00', 'idempotency_key' => 'k1']);
        $this->assertSame($a->id, $again->id);
        $this->assertSame(1, Appointment::count());
    }

    public function test_manual_approval_deadline_expiry_releases_slot(): void
    {
        $b = $this->makeBusiness([], ['booking_approval' => 'manual', 'pending_deadline_hours' => 2]);
        $s = $this->service($b);
        $a = $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-17 10:00']);
        $this->assertSame('pending_approval', $a->status);
        $this->assertNotNull($a->approval_deadline_at);
        $times = fn () => collect($this->svc->availableSlots($b, $s, '2026-11-17'))->map(fn ($x) => $x['start']->format('H:i'));
        $this->assertNotContains('10:00', $times()); // pending blocks time

        $this->travel(3)->hours();
        $this->assertSame(1, $this->svc->expireOverdue());
        $this->assertSame('expired', $a->fresh()->status);
        $this->assertContains('10:00', $times());
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $b = $this->makeBusiness();
        $a = $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $this->service($b)->id, 'start' => '2026-11-16 10:00']);
        $this->svc->transition($a, 'completed');
        $this->expectException(RuleViolation::class);
        $this->svc->transition($a->fresh(), 'cancelled');
    }

    public function test_reschedule_keeps_original_when_new_slot_taken(): void
    {
        $b = $this->makeBusiness();
        $owner = $this->addMember($b, 'owner');
        $s = $this->service($b);
        $a = $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
        $this->svc->book($b, $this->customer($b, 'z@example.test'), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 14:00']);
        try {
            $this->svc->reschedule($a, '2026-11-16 14:00', null, $owner);
            $this->fail('Expected conflict');
        } catch (RuleViolation) {
        }
        $this->assertSame('confirmed', $a->fresh()->status);

        $new = $this->svc->reschedule($a, '2026-11-16 15:00', null, $owner);
        $this->assertSame('cancelled', $a->fresh()->status);
        $this->assertSame($a->id, $new->rescheduled_from_id);
        $this->assertSame(25000, $new->price_cents);
    }

    public function test_customer_cannot_cancel_inside_policy_window(): void
    {
        $b = $this->makeBusiness([], ['cancellation_hours' => 6]);
        $c = $this->customer($b);
        $a = $this->svc->book($b, $c, ['catalog_item_id' => $this->service($b)->id, 'start' => '2026-11-16 12:00']);
        $this->expectException(RuleViolation::class);
        $this->svc->customerCancel($a, $c);
    }

    public function test_price_snapshot_survives_catalog_change(): void
    {
        $b = $this->makeBusiness();
        $s = $this->service($b);
        $a = $this->svc->book($b, $this->customer($b), ['catalog_item_id' => $s->id, 'start' => '2026-11-16 10:00']);
        $s->update(['price_cents' => 99900, 'name' => 'Premium cut']);
        $this->assertSame(25000, $a->fresh()->price_cents);
        $this->assertSame('Haircut', $a->fresh()->service_name);
    }
}
