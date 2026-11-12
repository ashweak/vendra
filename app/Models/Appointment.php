<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use BelongsToBusiness, HasReference;

    public const REFERENCE_PREFIX = 'APT';

    public const STATUSES = [
        'pending_approval' => 'Pending approval',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'no_show' => 'No-show',
    ];

    public const OPEN_STATUSES = ['pending_approval', 'confirmed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'approval_deadline_at' => 'datetime'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function catalogItem(): BelongsTo { return $this->belongsTo(CatalogItem::class); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_id'); }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
