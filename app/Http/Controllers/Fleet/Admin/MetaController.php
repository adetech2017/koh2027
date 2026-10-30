<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\FleetAdmin;
use App\Models\Fleet\Handover;
use App\Models\Fleet\Recall;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\FleetReports;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    /**
     * Option lists the admin forms use, so they're defined once in config/fleet.php.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'lgas' => config('fleet.lgas'),
            'directorates' => (object) config('fleet.directorates'),
            'vehicle_types' => config('fleet.vehicle_types'),
            'colours' => config('fleet.colours'),
            'vehicle_statuses' => Vehicle::STATUSES,
            'id_types' => config('fleet.id_types'),
            'fuel_levels' => config('fleet.fuel_levels'),
            'handover_checklist' => config('fleet.handover_checklist'),
            'roles' => FleetAdmin::ROLES,
        ]);
    }

    public function dashboard(FleetReports $reports): JsonResponse
    {
        $vehicles = Vehicle::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return response()->json([
            'vehicles' => collect(Vehicle::STATUSES)->mapWithKeys(fn ($status) => [$status => (int) ($vehicles[$status] ?? 0)]),
            'beneficiaries' => [
                'total' => Beneficiary::count(),
                'with_vehicle' => Assignment::where('status', 'active')->distinct()->count('beneficiary_id'),
            ],
            'recalls' => [
                'open' => Recall::whereHas('assignments', fn ($q) => $q->where('status', 'active'))->count(),
                'vehicles_outstanding' => Assignment::where('status', 'active')->whereNotNull('recall_id')->count(),
            ],
            'evaluations' => $reports->dashboard(),
            'recent_handovers' => Handover::with('assignment.vehicle', 'assignment.beneficiary')
                ->latest('occurred_at')->limit(8)->get()
                ->map(fn (Handover $handover) => [
                    'id' => $handover->id,
                    'reference' => $handover->reference(),
                    'type' => $handover->type,
                    'occurred_at' => $handover->occurred_at->toIso8601String(),
                    'vehicle' => $handover->assignment->vehicle->displayPlate(),
                    'beneficiary' => $handover->assignment->beneficiary->full_name,
                ]),
        ]);
    }
}
