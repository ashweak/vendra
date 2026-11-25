<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Customer records are business-specific: the same person at two businesses has two records (P7). */
class Customer extends Model
{
    use BelongsToBusiness;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['marketing_consent' => false];

    protected $guarded = ['id', 'business_id'];

    protected function casts(): array
    {
        return ['marketing_consent' => 'boolean', 'last_activity_at' => 'datetime'];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }

    /** Adds completed_visits (finished bookings + completed orders) for "returning customer" badges, in one query. */
    public function scopeWithVisits($q)
    {
        return $q->withCount([
            'appointments as completed_bookings' => fn ($a) => $a->where('status', 'completed'),
            'orders as completed_orders' => fn ($o) => $o->where('status', 'completed'),
        ]);
    }

    /** Finished bookings and completed orders, not counting $current (the record being viewed). */
    public function pastVisits(?\Illuminate\Database\Eloquent\Model $current = null): int
    {
        $count = isset($this->completed_bookings)
            ? (int) $this->completed_bookings + (int) $this->completed_orders
            : $this->appointments()->where('status', 'completed')->count() + $this->orders()->where('status', 'completed')->count();
        if ($current && ($current->status ?? null) === 'completed'
            && ($current instanceof Appointment || $current instanceof Order) && $current->customer_id === $this->id) {
            $count--;
        }

        return max(0, $count);
    }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
    public function consents(): HasMany { return $this->hasMany(Consent::class); }
    public function privacyRequests(): HasMany { return $this->hasMany(PrivacyRequest::class); }
}
