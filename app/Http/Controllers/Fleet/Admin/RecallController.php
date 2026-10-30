<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Recall;
use App\Services\Fleet\FleetNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Committee decisions to call vehicles back. Each vehicle stays "recalled" until its return form is done.
 */
class RecallController extends Controller
{
    public function index(): JsonResponse
    {
        $recalls = $this->withProgress(Recall::query())->with('creator')->latest()->get();

        return response()->json(['data' => $recalls->map->toApi()]);
    }

    public function show(Recall $recall): JsonResponse
    {
        $recall = $this->withProgress(Recall::whereKey($recall->id))->with('creator')->first();

        return response()->json(['recall' => $recall->toApi()]);
    }

    public function store(Request $request, FleetNotifier $notifier): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'return_by' => ['required', 'date', 'after_or_equal:today'],
            'all_active' => ['sometimes', 'boolean'],
            'assignment_ids' => ['required_without:all_active', 'array'],
            'assignment_ids.*' => ['integer'],
        ]);

        $recall = DB::transaction(function () use ($data) {
            // Only vehicles still out and not already on another recall
            $assignments = Assignment::where('status', 'active')
                ->whereNull('recall_id')
                ->when(empty($data['all_active']), fn ($q) => $q->whereIn('id', $data['assignment_ids'] ?? []))
                ->lockForUpdate()
                ->get();

            if ($assignments->isEmpty()) {
                throw ValidationException::withMessages(['assignment_ids' => 'None of the chosen vehicles are out with beneficiaries (or they are already recalled).']);
            }

            $recall = Recall::create([
                'title' => $data['title'],
                'reason' => $data['reason'] ?? null,
                'return_by' => $data['return_by'],
                'created_by' => auth('fleet_admin')->id(),
            ]);

            Assignment::whereIn('id', $assignments->pluck('id'))->update(['recall_id' => $recall->id, 'recalled_at' => now()]);

            return $recall;
        });

        AuditLog::record('recall.created', $recall, ['vehicles' => $recall->assignments()->count()]);

        $recall->assignments()->with('beneficiary', 'vehicle', 'recall')->each(fn (Assignment $assignment) => $notifier->vehicleRecalled($assignment));

        return response()->json(['recall' => $this->withProgress(Recall::whereKey($recall->id))->first()->toApi()], 201);
    }

    private function withProgress($query)
    {
        return $query->withCount([
            'assignments',
            'assignments as returned_count' => fn ($q) => $q->where('status', 'returned'),
        ]);
    }
}
