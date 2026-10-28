<?php

namespace App\Jobs\Fleet;

use App\Models\Fleet\Evaluation;
use App\Services\Fleet\FleetFiles;
use App\Services\Fleet\VideoTools;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Converts a submitted video to a compact MP4 for playback, makes a thumbnail, stores both on the
 * fleet disk and removes the uploaded original (its metadata is already on the evaluation).
 */
class ProcessEvaluationVideo implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public int $evaluationId) {}

    public function handle(VideoTools $video, FleetFiles $files): void
    {
        $evaluation = Evaluation::find($this->evaluationId);
        if (!$evaluation || $evaluation->processing_status === 'ready' || !$evaluation->working_path) {
            return;
        }

        $working = dirname($evaluation->working_path);
        $mp4 = "{$working}/processed.mp4";
        $jpg = "{$working}/thumbnail.jpg";

        $video->toMp4($evaluation->working_path, $mp4);
        $video->thumbnail($mp4, $jpg);

        $directory = 'evaluations/'.$evaluation->submitted_at->format('Y/m').'/'.$evaluation->id.'-'.Str::random(8);
        $files->disk()->put("{$directory}/video.mp4", fopen($mp4, 'rb'));
        $files->disk()->put("{$directory}/thumbnail.jpg", fopen($jpg, 'rb'));

        $evaluation->update([
            'video_path' => "{$directory}/video.mp4",
            'thumbnail_path' => "{$directory}/thumbnail.jpg",
            // Browser recordings often have no duration until converted
            'duration_seconds' => $video->inspect($mp4)['duration'] ?? $evaluation->duration_seconds,
            'processing_status' => 'ready',
            'processing_error' => null,
            'working_path' => null,
        ]);

        File::deleteDirectory($working);
    }

    public function failed(?\Throwable $e): void
    {
        // The uploaded file is kept so the job can be retried (php artisan queue:retry)
        Evaluation::whereKey($this->evaluationId)->update([
            'processing_status' => 'failed',
            'processing_error' => Str::limit($e?->getMessage() ?? 'Unknown error', 1000),
        ]);
    }
}
