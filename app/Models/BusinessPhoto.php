<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Services\ImageStore;
use Illuminate\Database\Eloquent\Model;

class BusinessPhoto extends Model
{
    use BelongsToBusiness;

    public const MAX = 8;

    protected $guarded = ['id', 'business_id'];

    public function url(): ?string
    {
        return ImageStore::url($this->path);
    }
}
