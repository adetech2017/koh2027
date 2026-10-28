<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Who has which vehicle. Assignments are created by the handover form and ended by the return form.
 */
class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $assignments = Assignment::query()
            ->with('vehicle', 'beneficiary', 'recall', 'handoverForm', 'returnForm')
            ->when($request->query('status') === 'active', fn ($q) => $q->where('status', 'active')->whereNull('recall_id'))
            ->when($request->query('status') === 'recalled', fn ($q) => $q->where('status', 'active')->whereNotNull('recall_id'))
            ->when($request->query('status') === 'out', fn ($q) => $q->where('status', 'active'))
            ->when($request->query('status') === 'returned', fn ($q) => $q->where('status', 'returned'))
            ->when($request->query('recall_id'), fn ($q, $id) => $q->where('recall_id', $id))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', '%'.Vehicle::normalizePlate($search).'%'))
                ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', "%{$search}%"))))
            ->when($request->query('lga'), fn ($q, $lga) => $q->whereHas('beneficiary', fn ($b) => $b->where('lga', $lga)))
            ->latest('handed_over_at')
            ->paginate(min((int) $request->query('per_page', 25), 200))
            ->through(fn (Assignment $assignment) => $assignment->toApi());

        return response()->json($assignments);
    }

    public function show(Assignment $assignment): JsonResponse
    {
        $assignment->load('vehicle', 'beneficiary', 'recall', 'handoverForm', 'returnForm');

        return response()->json([
            'assignment' => $assignment->toApi() + [
                'handover_odometer_km' => $assignment->handoverForm?->odometer_km,
            ],
        ]);
    }
}
