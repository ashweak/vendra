<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogItem extends Model
{
    use BelongsToBusiness;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['stock_reserved' => 0, 'is_active' => true, 'requires_approval' => false];

    protected $guarded = ['id', 'business_id', 'stock_reserved'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'requires_approval' => 'boolean'];
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function availableStock(): ?int
    {
        return $this->stock_on_hand === null ? null : max(0, $this->stock_on_hand - $this->stock_reserved);
    }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeServices($q) { return $q->where('type', 'service'); }
    public function scopeProducts($q) { return $q->where('type', 'product'); }
}
