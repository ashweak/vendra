<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id'];
}
