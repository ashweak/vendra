<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded pictures safely on the media disk (the local public disk, or Cloudflare R2 when
 * VENDRA_MEDIA_DISK=r2, so pictures survive restarts on hosts with temporary storage):
 *  - decodes the file with GD, so anything that isn't a real JPG/PNG/WebP image is rejected;
 *  - applies the phone's rotation, then re-encodes, which drops all metadata (EXIF, GPS location, camera details);
 *  - shrinks to a size that fits its use, or crops to a square for logos and profile pictures;
 *  - also saves smaller copies (480 and 960 px wide) of large pictures, so phones download less (see srcset()).
 *
 * File names carry the width: "<random>-1600w.webp", with copies "<random>-1600w-480.webp".
 */
class ImageStore
{
    /** Uploads larger than this many pixels are refused before decoding (protects server memory). */
    private const MAX_PIXELS = 40_000_000;

    public const RULES = ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    /** Smaller copies made for pictures wider than each size. */
    public const VARIANTS = [480, 960];

    public static function disk(): string
    {
        return (string) config('vendra.media_disk', 'public');
    }

    public function store(UploadedFile $file, string $dir, int $maxWidth, int $maxHeight, bool $square = false, string $field = 'image'): string
    {
        return $this->storeBytes((string) file_get_contents($file->getRealPath()), $dir, $maxWidth, $maxHeight, $square, $field);
    }

    /** Same as store(), for a picture already on the server (used by the demo seeder). */
    public function storeFile(string $file, string $dir, int $maxWidth, int $maxHeight, bool $square = false): string
    {
        return $this->storeBytes((string) file_get_contents($file), $dir, $maxWidth, $maxHeight, $square, 'image');
    }

    private function storeBytes(string $bytes, string $dir, int $maxWidth, int $maxHeight, bool $square, string $field): string
    {
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw ValidationException::withMessages([$field => 'Please upload a JPG, PNG or WebP picture.']);
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages([$field => 'That picture is too large. Please use one under 40 megapixels.']);
        }
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw ValidationException::withMessages([$field => 'We couldn’t read that picture. Please try another file.']);
        }
        $src = $this->applyOrientation($src, $bytes, $info[2]);

        $w = imagesx($src);
        $h = imagesy($src);
        [$sx, $sy, $sw, $sh] = [0, 0, $w, $h];
        if ($square) {
            $side = min($w, $h);
            [$sx, $sy, $sw, $sh] = [intdiv($w - $side, 2), intdiv($h - $side, 2), $side, $side];
            $maxHeight = $maxWidth;
        }
        $scale = min(1, $maxWidth / $sw, $maxHeight / $sh);
        $tw = max(1, (int) round($sw * $scale));
        $th = max(1, (int) round($sh * $scale));

        $dst = $this->resample($src, $sx, $sy, $sw, $sh, $tw, $th);
        [$encoded, $ext] = $this->encode($dst);

        $base = trim($dir, '/').'/'.Str::random(32).'-'.$tw.'w';
        $files = [$base.'.'.$ext => $encoded];
        foreach (self::VARIANTS as $vw) {
            if ($tw > $vw) {
                $vh = max(1, (int) round($th * $vw / $tw));
                $files[$base.'-'.$vw.'.'.$ext] = $this->encode($this->resample($dst, 0, 0, $tw, $th, $vw, $vh))[0];
            }
        }
        $disk = Storage::disk(self::disk());
        foreach ($files as $path => $data) {
            if (! $disk->put($path, $data)) {
                $disk->delete(array_keys($files));
                throw ValidationException::withMessages([$field => 'We couldn’t save that picture right now. Please try again.']);
            }
        }

        return array_key_first($files);
    }

    private function resample(\GdImage $src, int $sx, int $sy, int $sw, int $sh, int $tw, int $th): \GdImage
    {
        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);

        return $dst;
    }

    /** @return array{0: string, 1: string} encoded bytes and extension */
    private function encode(\GdImage $img): array
    {
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($img, null, 82);
            $ext = 'webp';
        } else {
            imagepng($img, null, 7);
            $ext = 'png';
        }

        return [(string) ob_get_clean(), $ext];
    }

    /** Deletes a picture and its smaller copies. */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::disk())->delete([$path, ...array_values(self::variants($path))]);
        }
    }

    /** Public URL for a stored picture. On the local disk it follows the current site, so it works on any host. */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return self::disk() === 'public' ? asset('storage/'.$path) : Storage::disk(self::disk())->url($path);
    }

    /** ' srcset="…" sizes="…"' for an <img>, or nothing when the picture has no smaller copies. */
    public static function attrs(?string $path, string $sizes): \Illuminate\Support\HtmlString
    {
        $srcset = self::srcset($path);

        return new \Illuminate\Support\HtmlString($srcset ? ' srcset="'.e($srcset).'" sizes="'.e($sizes).'"' : '');
    }

    /** Paths of the smaller copies, keyed by width, judged from the file name. */
    public static function variants(string $path): array
    {
        if (! preg_match('/^(.+-(\d+)w)\.(webp|png)$/', $path, $m)) {
            return [];
        }
        $out = [];
        foreach (self::VARIANTS as $vw) {
            if ((int) $m[2] > $vw) {
                $out[$vw] = $m[1].'-'.$vw.'.'.$m[3];
            }
        }

        return $out;
    }

    /** "srcset" value listing the copies and the original, or null when there are no copies. */
    public static function srcset(?string $path): ?string
    {
        if (! $path || ! ($variants = self::variants($path))) {
            return null;
        }
        preg_match('/-(\d+)w\.(webp|png)$/', $path, $m);
        $parts = [];
        foreach ($variants as $w => $p) {
            $parts[] = self::url($p).' '.$w.'w';
        }
        $parts[] = self::url($path).' '.$m[1].'w';

        return implode(', ', $parts);
    }

    private function applyOrientation(\GdImage $img, string $bytes, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle === 0) {
            return $img;
        }
        $rotated = imagerotate($img, $angle, 0);
        if (! $rotated) {
            return $img;
        }

        return $rotated;
    }
}
