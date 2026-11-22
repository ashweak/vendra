<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
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

    /** Unread notifications for one business, filtered in the database instead of in PHP. */
    public function unreadNotificationsFor(int $businessId)
    {
        return $this->unreadNotifications()->where('data->business_id', $businessId);
    }

    public function avatarUrl(): ?string
    {
        return \App\Services\ImageStore::url($this->avatar_path);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [''];

        return mb_strtoupper(mb_substr($parts[0], 0, 1).(count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ''));
    }
}
