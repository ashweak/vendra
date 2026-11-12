<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function catalogItem(): BelongsTo { return $this->belongsTo(CatalogItem::class); }
}
