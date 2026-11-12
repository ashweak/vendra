<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Versions are immutable once sent; acceptance refers to a specific version (P5). */
class QuotationVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function drafter(): BelongsTo { return $this->belongsTo(User::class, 'drafted_by'); }
}
