<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use BelongsToBusiness;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['is_internal' => false];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean', 'meta' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeCustomerVisible($q)
    {
        return $q->where('is_internal', false);
    }
}
