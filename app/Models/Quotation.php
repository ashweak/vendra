<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use BelongsToBusiness, HasReference;

    public const REFERENCE_PREFIX = 'QTE';

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'awaiting_owner_approval' => 'Awaiting owner approval',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'withdrawn' => 'Withdrawn',
    ];

    public const OPEN_STATUSES = ['submitted', 'under_review', 'awaiting_owner_approval', 'sent'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function versions(): HasMany { return $this->hasMany(QuotationVersion::class)->orderBy('version'); }
    public function currentVersion(): BelongsTo { return $this->belongsTo(QuotationVersion::class, 'current_version_id'); }
    public function acceptedVersion(): BelongsTo { return $this->belongsTo(QuotationVersion::class, 'accepted_version_id'); }
    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
