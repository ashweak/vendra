<?php

namespace App\Services\Ai;

use Carbon\CarbonImmutable;

/** Resolves relative dates/times in English, Tagalog and Taglish, in the business timezone (P6). */
class DateParser
{
    private const WEEKDAYS = [
        'sunday' => 0, 'linggo' => 0, 'monday' => 1, 'lunes' => 1, 'tuesday' => 2, 'martes' => 2,
        'wednesday' => 3, 'miyerkules' => 3, 'miyerkoles' => 3, 'thursday' => 4, 'huwebes' => 4,
        'friday' => 5, 'biyernes' => 5, 'byernes' => 5, 'saturday' => 6, 'sabado' => 6,
    ];

    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7,
        'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    private const TAGALOG_HOURS = ['una' => 1, 'dos' => 2, 'tres' => 3, 'kwatro' => 4, 'kuwatro' => 4, 'singko' => 5,
        'sais' => 6, 'siyete' => 7, 'syete' => 7, 'otso' => 8, 'nuwebe' => 9, 'diyes' => 10, 'dyes' => 10, 'onse' => 11, 'dose' => 12];

    public static function date(string $text, string $tz): ?string
    {
        $t = mb_strtolower($text);
        $today = CarbonImmutable::now($tz)->startOfDay();

        if (preg_match('/\b(20\d\d)-(\d{1,2})-(\d{1,2})\b/', $t, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/', $t, $m)) {
            $y = isset($m[3]) ? (strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3]) : (int) $today->year;
            $d = CarbonImmutable::create($y, (int) $m[1], (int) $m[2], 0, 0, 0, $tz);

            return ($d->lt($today) && ! isset($m[3]) ? $d->addYear() : $d)->toDateString();
        }
        if (preg_match('/\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?\s+(\d{1,2})\b/', $t, $m)
            || preg_match('/\b(\d{1,2})\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\b/', $t, $m2)) {
            [$mon, $day] = isset($m2) && $m2 ? [$m2[2], $m2[1]] : [$m[1], $m[2]];
            $d = CarbonImmutable::create((int) $today->year, self::MONTHS[$mon], (int) $day, 0, 0, 0, $tz);

            return ($d->lt($today) ? $d->addYear() : $d)->toDateString();
        }
        if (preg_match('/\b(day after tomorrow|sa makalawa|makalawa)\b/', $t)) {
            return $today->addDays(2)->toDateString();
        }
        if (preg_match('/\b(tomorrow|tmrw|tom)\b/', $t) || self::bukasMeansTomorrow($t)) {
            return $today->addDay()->toDateString();
        }
        if (preg_match('/\b(today|ngayon|ngayong araw|mamaya|tonight|later)\b/', $t)) {
            return $today->toDateString();
        }
        foreach (self::WEEKDAYS as $name => $dow) {
            if (preg_match('/\b'.$name.'\b/', $t)) {
                if ($name === 'linggo' && preg_match('/\b(next|this|ngayong|susunod na) linggo\b/', $t) && ! preg_match('/sa linggo/', $t)) {
                    continue; // "next linggo" = next week, ambiguous
                }
                $diff = ($dow - $today->dayOfWeek + 7) % 7;
                if (preg_match('/\bnext\b/', $t) && $diff === 0) {
                    $diff = 7;
                }

                return $today->addDays($diff)->toDateString();
            }
        }

        return null;
    }

    /** "bukas" means both "tomorrow" and "open". "Bukas ba kayo?" asks about opening hours. */
    public static function bukasMeansTomorrow(string $t): bool
    {
        if (! preg_match('/\bbukas\b/', $t)) {
            return false;
        }
        if (preg_match('/\bbukas\s+(ba\s+)?(kayo|po kayo|na ba|pa ba|ba)\b/', $t) && ! self::time($t)) {
            return false;
        }

        return true;
    }

    /** Returns 'H:i' or null. */
    public static function time(string $text): ?string
    {
        $t = mb_strtolower($text);
        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm|a\.m\.|p\.m\.)/', $t, $m)) {
            $h = (int) $m[1] % 12;
            if (str_starts_with($m[3], 'p')) {
                $h += 12;
            }

            return sprintf('%02d:%02d', $h, (int) ($m[2] ?? 0));
        }
        if (preg_match('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $t, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        if (preg_match('/\balas[- ]?(una|dos|tres|kwatro|kuwatro|singko|sais|siyete|syete|otso|nuwebe|diyes|dyes|onse|dose|\d{1,2})(?:\s*(y medya|30))?/', $t, $m)) {
            $h = is_numeric($m[1]) ? (int) $m[1] : self::TAGALOG_HOURS[$m[1]];
            $min = ! empty($m[2]) ? 30 : 0;
            if (preg_match('/(ng|sa) (hapon|gabi)/', $t) && $h < 12) {
                $h += 12;
            } elseif (! preg_match('/(ng|sa) umaga/', $t) && $h >= 1 && $h <= 6) {
                $h += 12; // alas tres without qualifier → afternoon
            }

            return sprintf('%02d:%02d', $h, $min);
        }
        if (preg_match('/\b(noon|tanghali)\b/', $t)) {
            return '12:00';
        }

        return null;
    }
}
