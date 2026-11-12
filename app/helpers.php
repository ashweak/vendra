<?php

use App\Support\Money;

if (! function_exists('peso')) {
    function peso(?int $cents): string
    {
        return Money::format($cents);
    }
}

if (! function_exists('local_time')) {
    /** Format a UTC timestamp in a business timezone. */
    function local_time($dt, string $tz, string $format = 'M j, Y g:i A'): string
    {
        return $dt ? $dt->copy()->timezone($tz)->format($format) : '—';
    }
}

if (! function_exists('status_tone')) {
    function status_tone(string $status): string
    {
        return match ($status) {
            'confirmed', 'completed', 'accepted', 'verified', 'active' => 'green',
            'pending_approval', 'submitted', 'under_review', 'awaiting_owner_approval', 'awaiting_customer',
            'sent', 'waiting_for_staff', 'unpaid', 'trial', 'past_due' => 'amber',
            'preparing', 'ready_for_pickup', 'dispatched', 'in_progress', 'ai_active', 'staff_active' => 'indigo',
            default => 'gray',
        };
    }
}
