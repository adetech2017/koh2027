<?php

namespace App\Http\Controllers\Fleet\Beneficiary;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    /**
     * The vehicle the signed-in beneficiary currently has, and any recall notice for it.
     */
    public function show(): JsonResponse
    {
        $assignment = auth('fleet_beneficiary')->user()->currentAssignment()->with('vehicle', 'recall')->first();

        if (!$assignment) {
            return response()->json(['assignment' => null]);
        }

        $vehicle = $assignment->vehicle;

        return response()->json([
            'assignment' => [
                'id' => $assignment->id,
                'handed_over_at' => $assignment->handed_over_at->toIso8601String(),
                'vehicle' => [
                    'plate_number' => $vehicle->displayPlate(),
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'colour' => $vehicle->colour,
                    'vehicle_type' => config('fleet.vehicle_types')[$vehicle->vehicle_type] ?? $vehicle->vehicle_type,
                ],
                'recall' => $assignment->recall ? [
                    'title' => $assignment->recall->title,
                    'reason' => $assignment->recall->reason,
                    'return_by' => $assignment->recall->return_by->toDateString(),
                ] : null,
            ],
        ]);
    }
}
