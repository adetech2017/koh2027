<?php

namespace App\Services\Fleet;

use App\Jobs\Fleet\ProcessEvaluationVideo;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationChallenge;
use App\Models\Fleet\UploadSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a finished upload plus the beneficiary's checklist into an evaluation. The checks that
 * can be answered in a second (is it a video, how long, was it recorded in this window, has this
 * exact file been sent before) happen here so the beneficiary hears straight away; converting the
 * video for playback happens afterwards in a queued job.
 */
class EvaluationSubmission
{
    public function __construct(
        private ChunkedUploads $uploads,
        private VideoTools $video,
        private EvaluationTrust $trust,
    ) {}

    /**
     * @param  array<string, mixed>  $form  Validated fields from Beneficiary\EvaluationController::store
     */
    public function submit(Beneficiary $beneficiary, UploadSession $session, array $form): Evaluation
    {
        $period = $session->period;
        $assignment = $period->assignment;

        if (!$period->acceptsSubmissions()) {
            throw ValidationException::withMessages(['upload_id' => 'This evaluation window has closed or already has a video.']);
        }

        $path = $this->uploads->assemble($session);
        $hash = hash_file('sha256', $path);

        try {
            if (Evaluation::where('file_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['upload_id' => 'This exact video has already been submitted. Please record a new one.']);
            }

            $info = $this->video->inspect($path)
                ?? throw ValidationException::withMessages(['upload_id' => "This file couldn't be read as a video. Please record or choose another."]);

            // Browser recordings (WebM) often carry no duration; the app's own timer is used for those
            $duration = $info['duration'] ?? ($form['source'] === 'live' ? (float) ($form['client_duration'] ?? 0) : null);
            $limits = config('fleet.evaluations');
            if ($duration !== null && $duration < $limits['min_seconds']) {
                throw ValidationException::withMessages(['upload_id' => "The video is too short. Please record at least {$limits['min_seconds']} seconds."]);
            }
            if ($duration !== null && $duration > $limits['max_seconds']) {
                throw ValidationException::withMessages(['upload_id' => 'The video is too long. Please keep it under '.intdiv($limits['max_seconds'], 60).' minutes.']);
            }

            if ($form['source'] === 'gallery' && $info['recorded_at'] && $info['recorded_at']->lessThan($period->starts_at)) {
                throw ValidationException::withMessages(['upload_id' => sprintf(
                    'This video was recorded on %s, before this evaluation window opened on %s. Please record a new video.',
                    $info['recorded_at']->timezone('Africa/Lagos')->format('j M Y'),
                    $period->starts_at->timezone('Africa/Lagos')->format('j M Y'),
                )]);
            }

            $challenge = $this->validChallenge($form['challenge_id'] ?? null, $beneficiary, $session);
            $device = isset($form['latitude'])
                ? ['latitude' => (float) $form['latitude'], 'longitude' => (float) $form['longitude']]
                : null;
            $previousMileage = $this->previousMileage($assignment->id);

            [$level, $flags] = $this->trust->assess($form['source'], (bool) $challenge, $device, $info, $period, (int) $form['mileage_km'], $previousMileage);

            $evaluation = DB::transaction(function () use ($beneficiary, $session, $form, $period, $assignment, $challenge, $level, $flags, $hash, $path, $info, $duration) {
                $evaluation = Evaluation::create([
                    'period_id' => $period->id,
                    'assignment_id' => $assignment->id,
                    'beneficiary_id' => $beneficiary->id,
                    'vehicle_id' => $assignment->vehicle_id,
                    'source' => $form['source'],
                    'challenge_id' => $challenge?->id,
                    'challenge_code' => $challenge?->code,
                    'trust_level' => $level,
                    'trust_flags' => $flags,
                    'original_filename' => $session->filename,
                    'original_mime' => $session->mime,
                    'original_size' => $session->size,
                    'file_hash' => $hash,
                    'working_path' => $path,
                    'processing_status' => 'processing',
                    'duration_seconds' => $duration,
                    'recorded_at' => $info['recorded_at'],
                    'metadata' => collect($info)->except(['recorded_at', 'latitude', 'longitude'])->all(),
                    'device_latitude' => $form['latitude'] ?? null,
                    'device_longitude' => $form['longitude'] ?? null,
                    'device_accuracy_m' => isset($form['accuracy_m']) ? (int) round($form['accuracy_m']) : null,
                    'metadata_latitude' => $info['latitude'],
                    'metadata_longitude' => $info['longitude'],
                    'mileage_km' => $form['mileage_km'],
                    'fuel_level' => $form['fuel_level'],
                    'has_damage' => $form['has_damage'],
                    'damage_notes' => $form['damage_notes'] ?? null,
                    'branding_intact' => $form['branding_intact'],
                    'branding_notes' => $form['branding_notes'] ?? null,
                    'notes' => $form['notes'] ?? null,
                    'review_status' => 'pending',
                    'submitted_at' => now(),
                ]);

                $period->update(['status' => 'submitted']);
                $session->update(['completed_at' => now()]);
                $challenge?->update(['used_at' => now()]);

                return $evaluation;
            });
        } catch (\Throwable $e) {
            // Nothing was saved, so the joined file has no owner
            $this->uploads->discard($session);
            $session->delete();

            throw $e;
        }

        AuditLog::record('evaluation.submitted', $evaluation, ['trust_level' => $level, 'flags' => $flags], $beneficiary);
        ProcessEvaluationVideo::dispatch($evaluation->id);

        return $evaluation;
    }

    /**
     * The code counts only if it was issued to this beneficiary, for this window, hasn't been
     * used, and the upload started within a few minutes of it (i.e. it was on screen while recording).
     */
    private function validChallenge(?int $id, Beneficiary $beneficiary, UploadSession $session): ?EvaluationChallenge
    {
        if (!$id) {
            return null;
        }

        $challenge = EvaluationChallenge::where('id', $id)
            ->where('beneficiary_id', $beneficiary->id)
            ->where('period_id', $session->period_id)
            ->whereNull('used_at')
            ->first();

        return $challenge
            && $session->created_at->greaterThanOrEqualTo($challenge->created_at)
            && $session->created_at->lessThanOrEqualTo($challenge->expires_at)
                ? $challenge
                : null;
    }

    /**
     * The last odometer reading for this assignment: the latest evaluation, else the handover form.
     */
    private function previousMileage(int $assignmentId): ?int
    {
        return Evaluation::where('assignment_id', $assignmentId)->latest('submitted_at')->value('mileage_km')
            ?? \App\Models\Fleet\Handover::where('assignment_id', $assignmentId)->where('type', 'handover')->value('odometer_km');
    }
}
