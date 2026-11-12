<?php

namespace App\Services;

use App\Exceptions\RuleViolation;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Appointment lifecycle (P5). Pending and confirmed bookings block staff time.
 * Availability is rechecked inside a locked transaction before saving.
 */
class AppointmentService
{
    public const BLOCKING = ['pending_approval', 'confirmed'];

    public const TRANSITIONS = [
        'pending_approval' => ['confirmed', 'declined', 'cancelled', 'expired'],
        'confirmed' => ['completed', 'cancelled', 'no_show'],
    ];

    /** Staff eligible for a service: explicitly linked staff, else every active member with a schedule. */
    public function eligibleStaff(Business $business, CatalogItem $service): Collection
    {
        $linked = $service->staff()->pluck('users.id');
        $query = $business->activeStaff();
        if ($linked->isNotEmpty()) {
            $query->whereIn('users.id', $linked);
        } else {
            $query->whereIn('users.id', $business->staffSchedules()->pluck('user_id'));
        }

        return $query->get();
    }

    /**
     * Available start times for a local date. Returns [['start' => CarbonImmutable(local), 'staff_id' => ?int, 'staff_name' => ?string]].
     * A business without staff schedules is treated as one shared calendar (staff_id null).
     */
    public function availableSlots(Business $business, CatalogItem $service, string $localDate, ?int $staffId = null): array
    {
        $tz = $business->timezone;
        $day = CarbonImmutable::parse($localDate, $tz)->startOfDay();
        $now = CarbonImmutable::now($tz);
        $horizon = $now->addDays((int) $business->setting('booking_horizon_days'));
        if ($day->lt($now->startOfDay()) || $day->gt($horizon)) {
            return [];
        }

        $hours = $business->hours()->where('weekday', $day->dayOfWeek)->first();
        if (! $hours || $hours->is_closed || ! $hours->opens_at) {
            return [];
        }
        $open = $day->setTimeFromTimeString($hours->opens_at);
        $close = $day->setTimeFromTimeString($hours->closes_at);
        $duration = max(15, (int) ($service->duration_minutes ?? 60));
        $interval = (int) $business->setting('slot_interval_minutes');
        $earliest = $now->addMinutes((int) $business->setting('booking_lead_minutes'));

        $staff = $this->eligibleStaff($business, $service);
        $calendars = [];
        if ($staff->isEmpty()) {
            if ($business->staffSchedules()->exists()) {
                return []; // business uses staff schedules but nobody can perform this service
            }
            $calendars[] = ['id' => null, 'name' => null, 'from' => $open, 'to' => $close];
        } else {
            foreach ($staff as $member) {
                if ($staffId && (int) $member->id !== (int) $staffId) {
                    continue;
                }
                foreach ($business->staffSchedules()->where('user_id', $member->id)->where('weekday', $day->dayOfWeek)->get() as $s) {
                    $from = $day->setTimeFromTimeString($s->starts_at)->max($open);
                    $to = $day->setTimeFromTimeString($s->ends_at)->min($close);
                    if ($from->lt($to)) {
                        $calendars[] = ['id' => $member->id, 'name' => $member->name, 'from' => $from, 'to' => $to];
                    }
                }
            }
        }

        $busy = Appointment::forBusiness($business)
            ->whereIn('status', self::BLOCKING)
            ->where('starts_at', '<', $close->utc())
            ->where('ends_at', '>', $open->utc())
            ->get(['staff_id', 'starts_at', 'ends_at']);

        $slots = [];
        foreach ($calendars as $cal) {
            for ($t = $cal['from']; $t->addMinutes($duration)->lte($cal['to']); $t = $t->addMinutes($interval)) {
                if ($t->lt($earliest)) {
                    continue;
                }
                $end = $t->addMinutes($duration);
                $conflict = $busy->contains(fn ($a) => ($cal['id'] === null || (int) $a->staff_id === (int) $cal['id'] || $a->staff_id === null)
                    && $a->starts_at->lt($end) && $a->ends_at->gt($t));
                if (! $conflict) {
                    $slots[] = ['start' => $t, 'staff_id' => $cal['id'], 'staff_name' => $cal['name']];
                }
            }
        }
        usort($slots, fn ($a, $b) => $a['start'] <=> $b['start']);

        return $slots;
    }

    /**
     * Create a booking. $data: catalog_item_id, start (local 'Y-m-d H:i'), staff_id?, notes?, idempotency_key?, source?
     * Returns the existing appointment for a repeated idempotency key instead of creating a duplicate.
     */
    public function book(Business $business, Customer $customer, array $data, ?User $actor = null): Appointment
    {
        abort_unless((int) $customer->business_id === (int) $business->id, 404);

        if (! empty($data['idempotency_key'])) {
            $existing = Appointment::forBusiness($business)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        $service = CatalogItem::forBusiness($business)->services()->active()->find($data['catalog_item_id'] ?? 0);
        if (! $service) {
            throw new RuleViolation('That service is not available. Please choose another service.');
        }

        $source = $data['source'] ?? 'online';
        $start = CarbonImmutable::parse($data['start'], $business->timezone);
        $end = $start->addMinutes(max(15, (int) ($service->duration_minutes ?? 60)));
        $staffId = isset($data['staff_id']) && $data['staff_id'] !== '' ? (int) $data['staff_id'] : null;

        return DB::transaction(function () use ($business, $customer, $service, $start, $end, $staffId, $data, $actor, $source) {
            // Serialize bookings per business so two requests cannot take the same slot.
            Business::whereKey($business->id)->lockForUpdate()->first();

            if ($source === 'online') {
                $slot = collect($this->availableSlots($business, $service, $start->toDateString(), $staffId))
                    ->first(fn ($s) => $s['start']->eq($start));
                if (! $slot) {
                    throw new RuleViolation('Sorry, that time is no longer available. Please pick another slot.', 'slot_unavailable');
                }
                $staffId = $slot['staff_id'];
            } elseif ($this->hasConflict($business, $staffId, $start, $end)) {
                throw new RuleViolation('That staff member already has a booking at this time.', 'slot_unavailable');
            }

            $needsApproval = $source === 'online'
                && ($business->setting('booking_approval') === 'manual' || $service->requires_approval);

            $deadline = null;
            if ($needsApproval) {
                $deadline = CarbonImmutable::now()->addHours((int) $business->setting('pending_deadline_hours'));
                if ($deadline->gt($start)) {
                    $deadline = $start->utc();
                }
            }

            $appointment = new Appointment([
                'customer_id' => $customer->id,
                'catalog_item_id' => $service->id,
                'staff_id' => $staffId,
                'service_name' => $service->name,
                'price_cents' => $service->price_cents,
                'starts_at' => $start->utc(),
                'ends_at' => $end->utc(),
                'status' => $needsApproval ? 'pending_approval' : 'confirmed',
                'source' => $source,
                'approval_deadline_at' => $deadline,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor?->id,
                // Assignment policy: the performing staff member, else the staff user who created it.
                'assigned_to' => $staffId ?? $actor?->id,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'rescheduled_from_id' => $data['rescheduled_from_id'] ?? null,
            ]);
            $appointment->business_id = $business->id;
            $appointment->save();

            AuditLogger::log($business->id, 'appointment.created', $appointment, [
                'status' => $appointment->status, 'starts_at' => $appointment->starts_at->toIso8601String(), 'source' => $source,
            ], actorType: $actor ? 'user' : 'customer', actorId: $actor?->id ?? $customer->id);

            return $appointment;
        });
    }

    public function hasConflict(Business $business, ?int $staffId, CarbonImmutable $start, CarbonImmutable $end, ?int $ignoreId = null): bool
    {
        return Appointment::forBusiness($business)
            ->whereIn('status', self::BLOCKING)
            ->when($staffId, fn ($q) => $q->where(fn ($q) => $q->where('staff_id', $staffId)->orWhereNull('staff_id')))
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->exists();
    }

    public function transition(Appointment $appointment, string $to, ?User $actor = null, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $to, $actor, $reason) {
            $fresh = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $allowed = self::TRANSITIONS[$fresh->status] ?? [];
            if (! in_array($to, $allowed, true)) {
                throw new RuleViolation("A {$fresh->statusLabel()} booking cannot be changed to ".(Appointment::STATUSES[$to] ?? $to).'.');
            }
            $from = $fresh->status;
            $fresh->status = $to;
            $fresh->status_reason = $reason;
            $fresh->save();

            AuditLogger::log($fresh->business_id, "appointment.$to", $fresh, ['status' => [$from, $to]], $reason,
                $actor ? 'user' : 'system', $actor?->id);

            return $fresh;
        });
    }

    /** Customer self-service cancellation within policy; outside policy requires staff review. */
    public function customerCancel(Appointment $appointment, Customer $customer): Appointment
    {
        abort_unless((int) $appointment->customer_id === (int) $customer->id, 404);
        $business = $appointment->business;
        $hours = (int) $business->setting('cancellation_hours');
        if ($appointment->status === 'confirmed' && now()->diffInMinutes($appointment->starts_at, false) < $hours * 60) {
            throw new RuleViolation("Cancellations less than {$hours} hours before the appointment need staff review. Please message the business.");
        }
        $updated = $this->transition($appointment, 'cancelled', null, 'Cancelled by customer');
        AuditLogger::log($business->id, 'appointment.customer_cancelled', $updated, [], null, 'customer', $customer->id);

        return $updated;
    }

    /** Rescheduling keeps the old booking unless the new slot is secured (P5). */
    public function reschedule(Appointment $old, string $newStartLocal, ?int $staffId, User $actor, ?string $reason = null): Appointment
    {
        $business = $old->business;

        return DB::transaction(function () use ($old, $newStartLocal, $staffId, $actor, $reason, $business) {
            Business::whereKey($business->id)->lockForUpdate()->first();
            $old = Appointment::whereKey($old->id)->lockForUpdate()->firstOrFail();
            if (! in_array($old->status, self::BLOCKING, true)) {
                throw new RuleViolation('Only pending or confirmed bookings can be rescheduled.');
            }
            $start = CarbonImmutable::parse($newStartLocal, $business->timezone);
            $end = $start->addMinutes($old->starts_at->diffInMinutes($old->ends_at));
            $staffId ??= $old->staff_id;
            if ($this->hasConflict($business, $staffId, $start, $end, $old->id)) {
                throw new RuleViolation('The new time is not available. The original booking was kept.', 'slot_unavailable');
            }

            $new = $old->replicate(['reference', 'idempotency_key']);
            $new->starts_at = $start->utc();
            $new->ends_at = $end->utc();
            $new->staff_id = $staffId;
            $new->assigned_to = $staffId ?? $old->assigned_to;
            $new->rescheduled_from_id = $old->id;
            $new->status = 'confirmed';
            $new->approval_deadline_at = null;
            $new->save();

            $old->status = 'cancelled';
            $old->status_reason = 'Rescheduled to '.$new->reference.($reason ? " – $reason" : '');
            $old->save();

            AuditLogger::log($business->id, 'appointment.rescheduled', $new, [
                'from' => $old->reference, 'starts_at' => $new->starts_at->toIso8601String(),
            ], $reason);

            return $new;
        });
    }

    /** Scheduled: pending approvals past their deadline expire and release the slot. */
    public function expireOverdue(): int
    {
        $count = 0;
        Appointment::where('status', 'pending_approval')
            ->where('approval_deadline_at', '<', now())
            ->each(function (Appointment $a) use (&$count) {
                $this->transition($a, 'expired', null, 'Approval deadline passed');
                $count++;
            });

        return $count;
    }
}
