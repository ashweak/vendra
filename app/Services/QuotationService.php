<?php

namespace App\Services;

use App\Exceptions\RuleViolation;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Quotations with immutable versions; acceptance refers to a specific version (P5). */
class QuotationService
{
    public function submit(Business $business, Customer $customer, string $requirements, ?string $key, ?User $actor = null): Quotation
    {
        if ($key && $existing = Quotation::forBusiness($business)->where('idempotency_key', $key)->first()) {
            return $existing;
        }
        $q = new Quotation([
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'requirements' => $requirements,
            'created_by' => $actor?->id,
            'assigned_to' => $actor?->id,
            'idempotency_key' => $key,
        ]);
        $q->business_id = $business->id;
        $q->save();
        AuditLogger::log($business->id, 'quotation.submitted', $q, [], null, $actor ? 'user' : 'customer', $actor?->id ?? $customer->id);

        return $q;
    }

    /** Draft a new version. $lines: [['description' => string, 'quantity' => int, 'unit_price_cents' => int]] */
    public function draftVersion(Quotation $quote, array $lines, ?string $notes, string $validUntil, User $actor): QuotationVersion
    {
        if (! in_array($quote->status, ['submitted', 'under_review', 'awaiting_owner_approval'], true)) {
            throw new RuleViolation('A new version can only be drafted while the quotation is being reviewed.');
        }
        $lines = array_values(array_filter($lines, fn ($l) => trim($l['description'] ?? '') !== ''));
        if (! $lines) {
            throw new RuleViolation('Add at least one line item.');
        }
        $total = 0;
        foreach ($lines as &$l) {
            $l['quantity'] = max(1, (int) $l['quantity']);
            $l['unit_price_cents'] = max(0, (int) $l['unit_price_cents']);
            $l['line_total_cents'] = $l['quantity'] * $l['unit_price_cents'];
            $total += $l['line_total_cents'];
        }

        return DB::transaction(function () use ($quote, $lines, $total, $notes, $validUntil, $actor) {
            $q = Quotation::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $version = new QuotationVersion([
                'version' => (int) $q->versions()->max('version') + 1,
                'line_items' => $lines,
                'total_cents' => $total,
                'notes' => $notes,
                'valid_until' => $validUntil,
                'drafted_by' => $actor->id,
            ]);
            $q->versions()->save($version);
            $q->current_version_id = $version->id;
            $q->status = 'awaiting_owner_approval';
            $q->save();
            AuditLogger::log($q->business_id, 'quotation.version_drafted', $q, ['version' => $version->version, 'total_cents' => $total]);

            return $version;
        });
    }

    /** Owner approves the current version and sends it to the customer. */
    public function approveAndSend(Quotation $quote, User $owner): Quotation
    {
        return DB::transaction(function () use ($quote, $owner) {
            $q = Quotation::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($q->status !== 'awaiting_owner_approval' || ! $q->current_version_id) {
                throw new RuleViolation('Only a drafted quotation awaiting approval can be sent.');
            }
            $v = $q->currentVersion;
            $v->update(['approved_by' => $owner->id, 'approved_at' => now(), 'sent_at' => now()]);
            $q->status = 'sent';
            $q->save();
            AuditLogger::log($q->business_id, 'quotation.sent', $q, ['version' => $v->version]);

            return $q;
        });
    }

    /** Customer response: accept | decline | revise, always against a specific version. */
    public function respond(Quotation $quote, int $versionId, string $action, Customer $customer, ?string $note = null): Quotation
    {
        abort_unless((int) $quote->customer_id === (int) $customer->id, 404);

        return DB::transaction(function () use ($quote, $versionId, $action, $customer, $note) {
            $q = Quotation::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($q->status !== 'sent') {
                throw new RuleViolation('This quotation can no longer be answered.');
            }
            if ((int) $q->current_version_id !== (int) $versionId) {
                throw new RuleViolation('A newer version of this quotation exists. Please review the latest version.', 'stale_version');
            }
            $version = $q->currentVersion;
            if ($action === 'accept' && $version->valid_until->endOfDay()->isPast()) {
                // The scheduled vendra:expire job records the Expired status.
                throw new RuleViolation('This quotation has expired. Please request a new one.');
            }
            $q->status = match ($action) {
                'accept' => 'accepted',
                'decline' => 'declined',
                'revise' => 'under_review',
            };
            if ($action === 'accept') {
                $q->accepted_version_id = $version->id;
            }
            $q->save();
            AuditLogger::log($q->business_id, "quotation.customer_$action", $q, ['version' => $version->version], $note, 'customer', $customer->id);

            return $q;
        });
    }

    public function setStatus(Quotation $quote, string $to, User $actor, ?string $reason = null): Quotation
    {
        $allowed = [
            'submitted' => ['under_review', 'declined', 'withdrawn'],
            'under_review' => ['declined', 'withdrawn'],
            'awaiting_owner_approval' => ['under_review', 'withdrawn'],
            'sent' => ['withdrawn', 'expired'],
        ];
        if (! in_array($to, $allowed[$quote->status] ?? [], true)) {
            throw new RuleViolation('That status change is not allowed.');
        }
        $from = $quote->status;
        $quote->status = $to;
        $quote->save();
        AuditLogger::log($quote->business_id, "quotation.$to", $quote, ['status' => [$from, $to]], $reason);

        return $quote;
    }

    /** Convert an accepted quote into exactly one linked service request. Prevents duplicate conversions. */
    public function convertToServiceRequest(Quotation $quote, User $actor): ServiceRequest
    {
        return DB::transaction(function () use ($quote, $actor) {
            $q = Quotation::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if ($q->status !== 'accepted') {
                throw new RuleViolation('Only accepted quotations can be converted.');
            }
            if ($q->converted_id) {
                throw new RuleViolation('This quotation was already converted.', 'duplicate_conversion');
            }
            $v = $q->acceptedVersion;
            $sr = new ServiceRequest([
                'customer_id' => $q->customer_id,
                'status' => 'accepted',
                'description' => "From quotation {$q->reference} v{$v->version}: ".$q->requirements,
                'quotation_id' => $q->id,
                'created_by' => $actor->id,
                'assigned_to' => $q->assigned_to ?? $actor->id,
            ]);
            $sr->business_id = $q->business_id;
            $sr->save();
            $q->converted_type = 'service_request';
            $q->converted_id = $sr->id;
            $q->save();
            AuditLogger::log($q->business_id, 'quotation.converted', $q, ['service_request' => $sr->reference]);

            return $sr;
        });
    }

    public function expireOverdue(): int
    {
        $n = 0;
        Quotation::where('status', 'sent')->with('currentVersion')->each(function (Quotation $q) use (&$n) {
            if ($q->currentVersion && $q->currentVersion->valid_until->endOfDay()->isPast()) {
                $q->update(['status' => 'expired']);
                AuditLogger::log($q->business_id, 'quotation.expired', $q, [], 'Validity passed', 'system');
                $n++;
            }
        });

        return $n;
    }
}
