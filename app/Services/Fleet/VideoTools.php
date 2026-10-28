<?php

namespace App\Services\Fleet;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * ffprobe and ffmpeg: reads a video's own metadata (when and where it was recorded, by which
 * phone), converts it to a web-friendly MP4 and grabs a thumbnail.
 */
class VideoTools
{
    /**
     * @return array{duration: ?float, recorded_at: ?Carbon, latitude: ?float, longitude: ?float, make: ?string, model: ?string, format: ?string, width: ?int, height: ?int}|null
     *         null if the file isn't a readable video
     */
    public function inspect(string $path): ?array
    {
        $result = Process::timeout(60)->run([
            config('fleet.ffprobe'), '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', $path,
        ]);

        $probe = $result->successful() ? json_decode($result->output(), true) : null;
        $video = collect($probe['streams'] ?? [])->firstWhere('codec_type', 'video');

        if (!$video) {
            return null;
        }

        $tags = array_change_key_case($probe['format']['tags'] ?? [], CASE_LOWER);
        $created = $tags['creation_time'] ?? $tags['com.apple.quicktime.creationdate'] ?? $video['tags']['creation_time'] ?? null;
        $location = $tags['location'] ?? $tags['com.apple.quicktime.location.iso6709'] ?? $tags['location-eng'] ?? null;
        [$latitude, $longitude] = $this->parseIso6709($location);

        $duration = $probe['format']['duration'] ?? $video['duration'] ?? null;

        return [
            'duration' => is_numeric($duration) ? (float) $duration : null,
            'recorded_at' => $this->parseDate($created),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'make' => $tags['com.android.manufacturer'] ?? $tags['com.apple.quicktime.make'] ?? null,
            'model' => $tags['com.android.model'] ?? $tags['com.apple.quicktime.model'] ?? null,
            'format' => $probe['format']['format_name'] ?? null,
            'width' => $video['width'] ?? null,
            'height' => $video['height'] ?? null,
        ];
    }

    /**
     * H.264/AAC MP4 at most 720p, metadata dropped (it's kept on the evaluation record), with the
     * index at the front so playback starts before the whole file has downloaded.
     */
    public function toMp4(string $input, string $output): void
    {
        $this->ffmpeg([
            '-i', $input,
            '-vf', "scale='if(gt(iw,ih),min(1280,iw),-2)':'if(gt(iw,ih),-2,min(1280,ih))'",
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '96k',
            '-map_metadata', '-1', '-movflags', '+faststart',
            $output,
        ]);
    }

    public function thumbnail(string $input, string $output): void
    {
        $this->ffmpeg(['-ss', '1', '-i', $input, '-frames:v', '1', '-vf', 'scale=480:-2', '-q:v', '4', $output]);
    }

    private function ffmpeg(array $arguments): void
    {
        $result = Process::timeout(900)->run([config('fleet.ffmpeg'), '-y', '-loglevel', 'error', ...$arguments]);

        if (!$result->successful()) {
            throw new RuntimeException('ffmpeg failed: '.trim($result->errorOutput()));
        }
    }

    /**
     * "+06.6018+003.3515/" or "+06.6018+003.3515+012.000/" → [6.6018, 3.3515]
     */
    private function parseIso6709(?string $value): array
    {
        if ($value && preg_match('/^([+-]\d+(?:\.\d+)?)([+-]\d+(?:\.\d+)?)/', $value, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        return [null, null];
    }

    private function parseDate(?string $value): ?Carbon
    {
        try {
            $date = $value ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }

        // Some phones write 1970 or 1904 when the clock wasn't set; that's "no date", not a real date
        return $date && $date->year >= 2000 ? $date : null;
    }
}
