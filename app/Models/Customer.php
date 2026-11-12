<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Customer records are business-specific: the same person at two businesses has two records (P7). */
class Customer extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'business_id'];

    protected function casts(): array
    {
        return ['marketing_consent' => 'boolean', 'last_activity_at' => 'datetime'];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
    public function consents(): HasMany { return $this->hasMany(Consent::class); }
}
