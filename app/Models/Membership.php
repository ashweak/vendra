<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Support\PermissionMatrix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function can(string $ability): bool
    {
        return $this->is_active && PermissionMatrix::allows($this->role, $ability);
    }

    public function isOwner(): bool { return $this->role === 'owner'; }
    public function isManagerOrOwner(): bool { return in_array($this->role, ['owner', 'manager'], true); }

    /** Staff only see records assigned to them (P4). */
    public function canAccessRecord(?Model $record): bool
    {
        if (! $record || ! $this->is_active || (int) $record->business_id !== (int) $this->business_id) {
            return false;
        }

        return $this->isManagerOrOwner() || (int) $record->assigned_to === (int) $this->user_id;
    }
}
