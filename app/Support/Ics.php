<?php

namespace App\Support;

use App\Models\Appointment;

/** Builds an iCalendar (.ics) file so customers can add a booking to Google, Apple or Outlook calendars. */
class Ics
{
    public static function appointment(Appointment $a): string
    {
        $b = $a->business;
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'vendra.local';
        $details = array_filter([
            'Reference: '.$a->reference,
            $b->phone ? 'Phone: '.$b->phone : null,
            'Manage or cancel: '.route('public.track', $b),
        ]);
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Vendra//Bookings//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.strtolower($a->reference).'@'.$host,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$a->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$a->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::text($a->service_name.' – '.$b->name),
            'DESCRIPTION:'.self::text(implode("\n", $details)),
            $b->address ? 'LOCATION:'.self::text($b->address) : null,
            'STATUS:'.($a->status === 'confirmed' ? 'CONFIRMED' : 'TENTATIVE'),
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::text($a->service_name.' at '.$b->name),
            'TRIGGER:-PT1H',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), array_filter($lines))).("\r\n");
    }

    /** Escapes text values (RFC 5545 §3.3.11). */
    private static function text(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $value);
    }

    /** Folds lines longer than 75 bytes without splitting a UTF-8 character (RFC 5545 §3.1). */
    private static function fold(string $line): string
    {
        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            if (strlen($current) + strlen($char) > ($out === '' ? 75 : 74)) {
                $out .= ($out === '' ? '' : "\r\n ").$current;
                $current = '';
            }
            $current .= $char;
        }

        return $out === '' ? $current : $out."\r\n ".$current;
    }
}
