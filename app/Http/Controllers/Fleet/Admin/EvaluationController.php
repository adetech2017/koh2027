<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\EvaluationReview;
use App\Services\Fleet\FleetFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EvaluationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $evaluations = Evaluation::query()
            ->with('vehicle', 'beneficiary', 'period')
            ->when($request->query('trust_level'), fn ($q, $level) => $q->where('trust_level', $level))
            ->when($request->query('review_status'), fn ($q, $status) => $q->where('review_status', $status))
            ->when($request->query('processing_status'), fn ($q, $status) => $q->where('processing_status', $status))
            ->when($request->query('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->query('beneficiary_id'), fn ($q, $id) => $q->where('beneficiary_id', $id))
            ->when($request->query('lga'), fn ($q, $lga) => $q->whereHas('beneficiary', fn ($b) => $b->where('lga', $lga)))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', '%'.Vehicle::normalizePlate($search).'%'))
                ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', "%{$search}%"))))
            // The review queue works oldest first; everything else newest first
            ->when($request->query('review_status') === 'pending', fn ($q) => $q->oldest('submitted_at'), fn ($q) => $q->latest('submitted_at'))
            ->paginate(min((int) $request->query('per_page', 25), 100))
            ->through(fn (Evaluation $evaluation) => $evaluation->toAdminApi());

        return response()->json($evaluations);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        $evaluation->load('vehicle', 'beneficiary', 'period', 'reviewer');

        return response()->json(['evaluation' => $evaluation->toAdminApi()]);
    }

    public function review(Request $request, Evaluation $evaluation, EvaluationReview $review): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:300', 'required_if:decision,reject'],
        ], [
            'notes.required_if' => 'Say why it was rejected. The beneficiary sees this on WhatsApp and in the app.',
        ]);

        $evaluation = $review->decide($evaluation, $data['decision'], $data['notes'] ?? null, auth('fleet_admin')->user());

        return response()->json([
            'evaluation' => $evaluation->load('vehicle', 'beneficiary', 'period', 'reviewer')->toAdminApi(),
            // So the reviewer can go straight to the next one
            'next_id' => Evaluation::where('review_status', 'pending')->where('processing_status', 'ready')->oldest('submitted_at')->value('id'),
        ]);
    }

    public function video(Evaluation $evaluation, FleetFiles $files): Response
    {
        abort_unless($evaluation->video_path, 404);

        return $this->serve($files, $evaluation->video_path, 'video/mp4');
    }

    public function thumbnail(Evaluation $evaluation, FleetFiles $files): Response
    {
        abort_unless($evaluation->thumbnail_path, 404);

        return $this->serve($files, $evaluation->thumbnail_path, 'image/jpeg');
    }

    /**
     * Local files are sent with Range support so the video player can seek; on S3/Spaces the
     * browser is sent to a short-lived signed link instead.
     */
    private function serve(FleetFiles $files, string $path, string $type): Response
    {
        $disk = $files->disk();

        if (config('filesystems.disks.'.config('fleet.disk').'.driver') === 'local') {
            return response()->file($disk->path($path), ['Content-Type' => $type, 'Cache-Control' => 'private, max-age=86400']);
        }

        return redirect()->away($disk->temporaryUrl($path, now()->addMinutes(30)));
    }
}
