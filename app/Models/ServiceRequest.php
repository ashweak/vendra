<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequest extends Model
{
    use BelongsToBusiness, HasReference;

    public const REFERENCE_PREFIX = 'SRQ';

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'awaiting_customer' => 'Awaiting customer',
        'accepted' => 'Accepted',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled',
    ];

    public const OPEN_STATUSES = ['submitted', 'under_review', 'awaiting_customer', 'accepted', 'in_progress'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
