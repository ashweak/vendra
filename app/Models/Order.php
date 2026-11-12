<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToBusiness, HasReference;

    public const REFERENCE_PREFIX = 'ORD';

    public const STATUSES = [
        'pending_approval' => 'Pending approval',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'ready_for_pickup' => 'Ready for pickup',
        'dispatched' => 'Dispatched',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'declined' => 'Declined',
        'expired' => 'Expired',
    ];

    public const OPEN_STATUSES = ['pending_approval', 'confirmed', 'preparing', 'ready_for_pickup'];

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['payment_status' => 'unpaid', 'stock_deducted' => false];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reservation_expires_at' => 'datetime', 'stock_deducted' => 'boolean'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function items(): HasMany { return $this->hasMany(OrderItem::class); }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
