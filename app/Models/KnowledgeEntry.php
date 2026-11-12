<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class KnowledgeEntry extends Model
{
    use BelongsToBusiness;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['is_published' => false];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}
