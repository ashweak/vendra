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
        if (! $dt) {
            return '—';
        }
        $local = $dt->copy()->timezone($tz);
        if (app()->getLocale() === 'en') {
            return $local->format($format);
        }
        // Month and day names follow the page language; AM/PM stays as Filipinos write it.
        $out = '';
        for ($i = 0, $n = strlen($format); $i < $n; $i++) {
            $c = $format[$i];
            if ($c === '\\' && $i + 1 < $n) {
                $out .= $c.$format[++$i];
            } elseif ($c === 'A' || $c === 'a') {
                $out .= preg_replace('/(.)/', '\\\\$1', $local->format($c));
            } else {
                $out .= $c;
            }
        }

        return $local->locale(app()->getLocale())->translatedFormat($out);
    }
}

if (! function_exists('status_tone')) {
    function status_tone(string $status): string
    {
        return match ($status) {
            'confirmed', 'completed', 'accepted', 'verified', 'active', 'done' => 'green',
            'pending_approval', 'submitted', 'under_review', 'awaiting_owner_approval', 'awaiting_customer',
            'sent', 'waiting_for_staff', 'unpaid', 'trial', 'past_due', 'open' => 'amber',
            'preparing', 'ready_for_pickup', 'dispatched', 'in_progress', 'ai_active', 'staff_active' => 'indigo',
            default => 'gray',
        };
    }
}
