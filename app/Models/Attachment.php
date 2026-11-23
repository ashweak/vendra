<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Attachment extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function attachable(): MorphTo { return $this->morphTo(); }

    /** Streams the file from private storage (server disk or R2) for viewing in the browser. */
    public function inlineResponse(): StreamedResponse
    {
        $disk = Storage::disk(config('vendra.files_disk'));
        abort_unless($disk->exists($this->path), 404);
        $name = trim(str_replace(['/', '\\', '%', '"'], '-', Str::ascii((string) $this->original_name))) ?: 'attachment';

        return $disk->response($this->path, $name, ['Content-Type' => $this->mime ?: 'application/octet-stream'], 'inline');
    }
}
