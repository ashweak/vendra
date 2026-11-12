<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use BelongsToBusiness;

    /** Mirror database defaults so new in-memory models behave the same as loaded ones. */
    protected $attributes = ['requests' => 0, 'tokens' => 0, 'failures' => 0, 'warning_sent' => false];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['warning_sent' => 'boolean'];
    }
}
