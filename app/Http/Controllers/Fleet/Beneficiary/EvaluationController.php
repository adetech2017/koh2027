<?php

namespace App\Http\Controllers\Fleet\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\EvaluationChallenge;
use App\Models\Fleet\EvaluationPeriod;
use App\Models\Fleet\Handover;
use App\Models\Fleet\UploadSession;
use App\Services\Fleet\ChunkedUploads;
use App\Services\Fleet\EvaluationSchedule;
use App\Services\Fleet\EvaluationSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The beneficiary's two-weekly video: what's due, the on-screen code, the chunked upload and the
 * final submission with their checklist.
 */
class EvaluationController extends Controller
{
    public function __construct(private EvaluationSchedule $schedule) {}

    public function index(): JsonResponse
    {
        $assignment = auth('fleet_beneficiary')->user()->currentAssignment;

        if (!$assignment) {
            return response()->json(['current' => null, 'periods' => []]);
        }

        $current = $this->schedule->sync($assignment);
        $periods = $assignment->evaluationPeriods()->with('latestEvaluation')->orderByDesc('sequence')->get();

        return response()->json([
            'current' => $current ? $periods->firstWhere('id', $current->id)->toApi() : null,
            'periods' => $periods->map->toApi(),
            'last_mileage_km' => Evaluation::where('assignment_id', $assignment->id)->latest('submitted_at')->value('mileage_km')
                ?? Handover::where('assignment_id', $assignment->id)->where('type', 'handover')->value('odometer_km'),
            'limits' => collect(config('fleet.evaluations'))->only(['min_seconds', 'max_seconds', 'max_upload_mb', 'chunk_bytes']),
        ]);
    }

    /**
     * A fresh code to show on screen while recording live.
     */
    public function challenge(): JsonResponse
    {
        $period = $this->openPeriod();

        $challenge = EvaluationChallenge::create([
            'beneficiary_id' => auth('fleet_beneficiary')->id(),
            'period_id' => $period->id,
            'code' => (string) random_int(1000, 9999),
            'expires_at' => now()->addMinutes(config('fleet.evaluations.challenge_minutes')),
        ]);

        return response()->json(['id' => $challenge->id, 'code' => $challenge->code, 'expires_at' => $challenge->expires_at->toIso8601String()], 201);
    }

    public function startUpload(Request $request): JsonResponse
    {
        $maxBytes = config('fleet.evaluations.max_upload_mb') * 1024 * 1024;
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'mime' => ['required', 'string', 'max:100', 'starts_with:video/'],
            'size' => ['required', 'integer', 'min:1', "max:{$maxBytes}"],
        ], [
            'mime.starts_with' => 'Please choose a video file.',
            'size.max' => 'This video is too large (over '.config('fleet.evaluations.max_upload_mb').' MB). Please record a shorter one.',
        ]);

        $period = $this->openPeriod();
        $chunk = config('fleet.evaluations.chunk_bytes');

        $session = UploadSession::create($data + [
            'beneficiary_id' => auth('fleet_beneficiary')->id(),
            'period_id' => $period->id,
            'chunk_size' => $chunk,
            'total_chunks' => (int) ceil($data['size'] / $chunk),
        ]);

        return response()->json($this->uploadState($session, []), 201);
    }

    public function uploadStatus(UploadSession $upload, ChunkedUploads $uploads): JsonResponse
    {
        $this->authorizeUpload($upload);

        return response()->json($this->uploadState($upload, $uploads->received($upload)));
    }

    /**
     * One piece of the video, sent as the raw request body.
     */
    public function putChunk(Request $request, UploadSession $upload, int $index, ChunkedUploads $uploads): JsonResponse
    {
        $this->authorizeUpload($upload);
        $uploads->store($upload, $index, $request->getContent());

        return response()->json(['received' => count($uploads->received($upload))]);
    }

    public function store(Request $request, EvaluationSubmission $submission): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'source' => ['required', Rule::in(['live', 'gallery'])],
            'challenge_id' => ['nullable', 'integer'],
            'client_duration' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'mileage_km' => ['required', 'integer', 'min:0', 'max:2000000'],
            'fuel_level' => ['required', Rule::in(array_keys(config('fleet.fuel_levels')))],
            'has_damage' => ['required', 'boolean'],
            'damage_notes' => ['nullable', 'string', 'max:1000', 'required_if:has_damage,true,1'],
            'branding_intact' => ['required', 'boolean'],
            'branding_notes' => ['nullable', 'string', 'max:1000', 'required_if:branding_intact,false,0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'mileage_km.required' => 'Enter the mileage shown on the odometer.',
            'fuel_level.required' => 'Choose the fuel level.',
            'has_damage.required' => 'Please choose an answer.',
            'branding_intact.required' => 'Please choose an answer.',
            'damage_notes.required_if' => 'Describe the damage.',
            'branding_notes.required_if' => "Describe what's wrong with the branding.",
        ]);

        $upload = UploadSession::find($data['upload_id']);
        if (!$upload || $upload->completed_at) {
            throw ValidationException::withMessages(['upload_id' => 'This upload has expired. Please upload the video again.']);
        }
        $this->authorizeUpload($upload);

        $evaluation = $submission->submit(auth('fleet_beneficiary')->user(), $upload, $data);

        return response()->json(['evaluation' => $evaluation->toBeneficiaryApi()], 201);
    }

    private function openPeriod(): EvaluationPeriod
    {
        $assignment = auth('fleet_beneficiary')->user()->currentAssignment;
        $period = $assignment ? $this->schedule->sync($assignment) : null;

        if (!$period?->acceptsSubmissions()) {
            throw ValidationException::withMessages(['period' => $assignment
                ? 'Your evaluation for this period is already in. The next one opens on '.($period?->ends_at ?? now())->timezone('Africa/Lagos')->format('j M').'.'
                : "You don't have a vehicle at the moment."]);
        }

        return $period;
    }

    private function authorizeUpload(UploadSession $upload): void
    {
        abort_unless($upload->beneficiary_id === auth('fleet_beneficiary')->id() && !$upload->completed_at, 404);
    }

    private function uploadState(UploadSession $session, array $received): array
    {
        return [
            'upload_id' => $session->id,
            'chunk_size' => $session->chunk_size,
            'total_chunks' => $session->total_chunks,
            'received' => $received,
        ];
    }
}
