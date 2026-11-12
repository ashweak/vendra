<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = ['is_platform_admin' => false];

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'mfa_secret' => 'encrypted',
            'mfa_enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'memberships')->withPivot('role', 'is_active')->withTimestamps();
    }

    public function membershipFor(Business|int $business): ?Membership
    {
        $id = $business instanceof Business ? $business->id : $business;

        return $this->memberships()->where('business_id', $id)->where('is_active', true)->first();
    }

    /** Owners and platform admins must use MFA (P7). */
    public function requiresMfa(): bool
    {
        return $this->is_platform_admin
            || $this->memberships()->where('role', 'owner')->where('is_active', true)->exists();
    }

    public function hasMfa(): bool
    {
        return $this->mfa_enabled_at !== null && $this->mfa_secret !== null;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }
}
