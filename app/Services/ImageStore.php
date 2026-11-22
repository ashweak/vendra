<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Stores uploaded pictures safely on the public disk:
 *  - decodes the file with GD, so anything that isn't a real JPG/PNG/WebP image is rejected;
 *  - applies the phone's rotation, then re-encodes, which drops all metadata (EXIF, GPS location, camera details);
 *  - shrinks to a size that fits its use, or crops to a square for logos and profile pictures.
 */
class ImageStore
{
    /** Uploads larger than this many pixels are refused before decoding (protects server memory). */
    private const MAX_PIXELS = 40_000_000;

    public const RULES = ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'];

    public function store(UploadedFile $file, string $dir, int $maxWidth, int $maxHeight, bool $square = false, string $field = 'image'): string
    {
        $bytes = (string) file_get_contents($file->getRealPath());
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

        $dst = imagecreatetruecolor($tw, $th);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($dst, null, 82);
            $ext = 'webp';
        } else {
            imagepng($dst, null, 7);
            $ext = 'png';
        }
        $encoded = (string) ob_get_clean();

        $path = trim($dir, '/').'/'.Str::random(32).'.'.$ext;
        Storage::disk('public')->put($path, $encoded);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Public URL for a stored picture (relative to the current site, so it works on any host). */
    public static function url(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
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
