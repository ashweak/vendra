<?php

namespace App\Services;

use App\Exceptions\RuleViolation;
use App\Models\Business;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestService
{
    public const TRANSITIONS = [
        'submitted' => ['under_review', 'declined', 'cancelled'],
        'under_review' => ['awaiting_customer', 'accepted', 'declined', 'cancelled'],
        'awaiting_customer' => ['under_review', 'cancelled'],
        'accepted' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed'],
    ];

    public function submit(Business $business, Customer $customer, array $data, ?User $actor = null): ServiceRequest
    {
        if (! empty($data['idempotency_key']) && $existing = ServiceRequest::forBusiness($business)->where('idempotency_key', $data['idempotency_key'])->first()) {
            return $existing;
        }
        $sr = new ServiceRequest([
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null, // preference only, not a booking (P5)
            'created_by' => $actor?->id,
            'assigned_to' => $actor?->id,
            'idempotency_key' => $data['idempotency_key'] ?? null,
        ]);
        $sr->business_id = $business->id;
        $sr->save();
        AuditLogger::log($business->id, 'service_request.submitted', $sr, [], null, $actor ? 'user' : 'customer', $actor?->id ?? $customer->id);

        return $sr;
    }

    public function transition(ServiceRequest $sr, string $to, ?User $actor, ?string $reason = null): ServiceRequest
    {
        if (! in_array($to, self::TRANSITIONS[$sr->status] ?? [], true)) {
            throw new RuleViolation("A {$sr->statusLabel()} request cannot be changed to ".(ServiceRequest::STATUSES[$to] ?? $to).'.');
        }
        if ($to === 'in_progress' && ! $sr->assigned_to) {
            throw new RuleViolation('Assign the request to a staff member before starting work.');
        }
        $from = $sr->status;
        $sr->status = $to;
        $sr->status_reason = $reason;
        $sr->save();
        AuditLogger::log($sr->business_id, "service_request.$to", $sr, ['status' => [$from, $to]], $reason, $actor ? 'user' : 'customer', $actor?->id);

        return $sr;
    }
}
