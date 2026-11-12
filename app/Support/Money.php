<?php

namespace App\Support;

class Money
{
    public static function format(?int $cents): string
    {
        if ($cents === null) {
            return 'To be confirmed';
        }

        return '₱'.number_format($cents / 100, 2);
    }

    public static function toCents(string|float|int|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
