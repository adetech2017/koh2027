<?php

namespace App\Services\Fleet;

use App\Models\Fleet\UploadSession;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Stores the pieces of a video upload on local disk and joins them once all have arrived. Pieces
 * can arrive more than once (a retry after a dropped connection) and in any order.
 */
class ChunkedUploads
{
    public function directory(UploadSession $session): string
    {
        return storage_path('app/private/fleet-uploads/'.$session->id);
    }

    public function store(UploadSession $session, int $index, string $bytes): void
    {
        if ($index < 0 || $index >= $session->total_chunks) {
            throw ValidationException::withMessages(['chunk' => 'That piece number is out of range.']);
        }
        if (strlen($bytes) !== $session->chunkLength($index)) {
            throw ValidationException::withMessages(['chunk' => 'That piece arrived incomplete. Please retry it.']);
        }

        File::ensureDirectoryExists($this->directory($session));
        $path = $this->directory($session)."/{$index}.part";

        // Write then rename, so a half-written piece is never mistaken for a whole one
        File::put($path.'.tmp', $bytes);
        File::move($path.'.tmp', $path);
    }

    /**
     * @return array<int, int> The piece numbers received so far
     */
    public function received(UploadSession $session): array
    {
        return collect(File::glob($this->directory($session).'/*.part'))
            ->map(fn ($path) => (int) basename($path, '.part'))
            ->sort()->values()->all();
    }

    /**
     * Joins the pieces into one file and returns its path.
     */
    public function assemble(UploadSession $session): string
    {
        $missing = array_diff(range(0, $session->total_chunks - 1), $this->received($session));
        if ($missing) {
            throw ValidationException::withMessages(['upload_id' => 'The video hasn\'t finished uploading yet.']);
        }

        $directory = $this->directory($session);
        $output = $directory.'/video';
        $out = fopen($output, 'wb');
        for ($index = 0; $index < $session->total_chunks; $index++) {
            $in = fopen("{$directory}/{$index}.part", 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        if (filesize($output) !== $session->size) {
            throw ValidationException::withMessages(['upload_id' => 'The uploaded video is incomplete. Please upload it again.']);
        }

        foreach (File::glob($directory.'/*.part') as $part) {
            File::delete($part);
        }

        return $output;
    }

    public function discard(UploadSession $session): void
    {
        File::deleteDirectory($this->directory($session));
    }
}
