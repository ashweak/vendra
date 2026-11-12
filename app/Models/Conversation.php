<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use BelongsToBusiness;

    public const STATES = [
        'ai_active' => 'AI active',
        'waiting_for_staff' => 'Waiting for staff',
        'staff_active' => 'Staff active',
        'closed' => 'Closed',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    public function messages(): HasMany { return $this->hasMany(Message::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    public function aiMayReply(): bool
    {
        return $this->state === 'ai_active';
    }
}
