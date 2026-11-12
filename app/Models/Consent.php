<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use BelongsToBusiness;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'recorded_at' => 'datetime'];
    }
}
