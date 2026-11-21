<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyRequest extends Model
{
    use BelongsToBusiness;

    public const TYPES = ['correction' => 'Correct my details', 'deletion' => 'Delete my details'];

    protected $attributes = ['status' => 'open'];

    protected $guarded = ['id', 'business_id'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function handler(): BelongsTo { return $this->belongsTo(User::class, 'handled_by'); }
}
