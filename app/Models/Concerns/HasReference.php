<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/** Human-friendly references. A reference alone never unlocks private data (P4). */
trait HasReference
{
    public static function bootHasReference(): void
    {
        static::creating(function ($model) {
            if (! $model->reference) {
                do {
                    $ref = static::REFERENCE_PREFIX.'-'.strtoupper(Str::random(6));
                } while (static::where('reference', $ref)->exists());
                $model->reference = $ref;
            }
        });
    }
}
