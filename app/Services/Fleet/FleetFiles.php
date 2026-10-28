<?php

namespace App\Services\Fleet;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Stores fleet uploads on the private fleet disk (config('fleet.disk')).
 */
class FleetFiles
{
    // A drawn signature as a PNG is typically 10–60 KB
    private const MAX_SIGNATURE_BYTES = 1024 * 1024;

    public function disk(): Filesystem
    {
        return Storage::disk(config('fleet.disk'));
    }

    /**
     * True for a PNG data URI from the signature pad (data:image/png;base64,...) that really is a PNG.
     */
    public static function isPngDataUri(mixed $value): bool
    {
        if (!is_string($value) || !str_starts_with($value, 'data:image/png;base64,')) {
            return false;
        }

        $binary = base64_decode(substr($value, 22), true);

        return $binary !== false
            && strlen($binary) <= self::MAX_SIGNATURE_BYTES
            && @getimagesizefromstring($binary)['mime'] === 'image/png';
    }

    public function storeSignature(string $dataUri, string $directory): string
    {
        $path = $directory.'/'.Str::uuid().'.png';
        $this->disk()->put($path, base64_decode(substr($dataUri, 22), true));

        return $path;
    }

    /**
     * Phone photos are resized to at most 1600px and re-encoded as JPEG, which also drops
     * their EXIF data (the handover's own GPS reading is kept on the record instead).
     */
    public function storePhoto(UploadedFile $file, string $directory): string
    {
        $path = $directory.'/'.Str::uuid().'.jpg';
        $jpeg = Image::decode($file->getRealPath())->scaleDown(1600, 1600)->encode(new JpegEncoder(quality: 80, strip: true));
        $this->disk()->put($path, $jpeg->toString());

        return $path;
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function delete(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                $this->disk()->delete($path);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
