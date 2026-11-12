<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    use HasFactory;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['timezone' => 'Asia/Manila', 'category' => 'general', 'accent_color' => '#4f46e5', 'ai_tone' => 'friendly', 'ai_enabled' => true, 'status' => 'active', 'onboarding_step' => 1];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'settings' => 'array',
            'ai_enabled' => 'boolean',
            'activated_at' => 'datetime',
            'closing_requested_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? config("vendra.default_settings.$key", $default);
    }

    public function moduleEnabled(string $module): bool
    {
        return in_array($module, $this->modules ?? [], true);
    }

    public function isAcceptingSubmissions(): bool
    {
        return $this->status === 'active' && $this->activated_at !== null;
    }

    public function memberships(): HasMany { return $this->hasMany(Membership::class); }
    public function hours(): HasMany { return $this->hasMany(BusinessHour::class); }
    public function staffSchedules(): HasMany { return $this->hasMany(StaffSchedule::class); }
    public function catalogItems(): HasMany { return $this->hasMany(CatalogItem::class); }
    public function knowledgeEntries(): HasMany { return $this->hasMany(KnowledgeEntry::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function quotations(): HasMany { return $this->hasMany(Quotation::class); }
    public function serviceRequests(): HasMany { return $this->hasMany(ServiceRequest::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
    public function aiUsages(): HasMany { return $this->hasMany(AiUsage::class); }
    public function supportGrants(): HasMany { return $this->hasMany(SupportGrant::class); }
    public function subscription(): HasOne { return $this->hasOne(Subscription::class)->latestOfMany(); }

    public function activeStaff()
    {
        return User::query()
            ->join('memberships', 'memberships.user_id', '=', 'users.id')
            ->where('memberships.business_id', $this->id)
            ->where('memberships.is_active', true)
            ->whereNull('users.disabled_at')
            ->select('users.*', 'memberships.role');
    }

    public function owner(): ?User
    {
        return $this->activeStaff()->where('memberships.role', 'owner')->first();
    }
}
