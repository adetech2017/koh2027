<?php

namespace App\Services\Fleet;

use App\Models\Fleet\Assignment;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\FleetAdmin;
use App\Models\Fleet\Handover;
use App\Models\Fleet\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hands a vehicle to a beneficiary (starting an assignment) and takes it back (ending it),
 * each with its signed form, photos and location.
 */
class HandoverService
{
    public function __construct(private FleetFiles $files, private EvaluationSchedule $schedule) {}

    /**
     * @param  array<string, mixed>  $form  Validated handover form fields (see HandoverController::formRules)
     */
    public function handOver(Vehicle $vehicle, Beneficiary $beneficiary, array $form, FleetAdmin $officer): Handover
    {
        return $this->withStoredFiles($form, function (array $paths) use ($vehicle, $beneficiary, $form, $officer) {
            // Lock the vehicle so two officers can't hand it out at the same time
            $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();

            if ($vehicle->status !== 'available' || $vehicle->currentAssignment()->exists()) {
                throw ValidationException::withMessages(['vehicle_id' => 'This vehicle is not available. It may already have been handed over.']);
            }
            if (!$beneficiary->isActive()) {
                throw ValidationException::withMessages(['beneficiary_id' => 'This beneficiary is suspended.']);
            }

            $assignment = Assignment::create([
                'vehicle_id' => $vehicle->id,
                'beneficiary_id' => $beneficiary->id,
                'status' => 'active',
                'handed_over_at' => now(),
                'created_by' => $officer->id,
            ]);

            $handover = $this->createForm($assignment, 'handover', $form, $paths, $officer);
            $vehicle->update(['status' => 'assigned']);
            $this->schedule->sync($assignment);

            AuditLog::record('handover.created', $handover, ['vehicle_id' => $vehicle->id, 'beneficiary_id' => $beneficiary->id], $officer);

            return $handover;
        });
    }

    /**
     * @param  string  $vehicleStatus  What the vehicle becomes after the return: available or maintenance
     */
    public function receiveReturn(Assignment $assignment, array $form, FleetAdmin $officer, string $vehicleStatus): Handover
    {
        return $this->withStoredFiles($form, function (array $paths) use ($assignment, $form, $officer, $vehicleStatus) {
            $assignment = Assignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if (!$assignment->isActive()) {
                throw ValidationException::withMessages(['assignment' => 'This vehicle has already been returned.']);
            }

            $handedOutKm = $assignment->handoverForm()->value('odometer_km');
            if ($handedOutKm !== null && $form['odometer_km'] < $handedOutKm) {
                throw ValidationException::withMessages([
                    'odometer_km' => "The odometer can't be lower than at handover (".number_format($handedOutKm).' km).',
                ]);
            }

            $handover = $this->createForm($assignment, 'return', $form, $paths, $officer);
            $assignment->update(['status' => 'returned', 'returned_at' => now()]);
            $assignment->vehicle->update(['status' => $vehicleStatus]);
            $this->schedule->sync($assignment);

            AuditLog::record('return.created', $handover, ['vehicle_id' => $assignment->vehicle_id, 'beneficiary_id' => $assignment->beneficiary_id], $officer);

            return $handover;
        });
    }

    private function createForm(Assignment $assignment, string $type, array $form, array $paths, FleetAdmin $officer): Handover
    {
        $handover = $assignment->handovers()->create([
            'type' => $type,
            'odometer_km' => $form['odometer_km'],
            'fuel_level' => $form['fuel_level'],
            'checklist' => $form['checklist'],
            'notes' => $form['notes'] ?? null,
            'officer_id' => $officer->id,
            'officer_signature_path' => $paths['officer_signature'],
            'signed_by_name' => $form['signed_by_name'],
            'signer_signature_path' => $paths['signer_signature'],
            'latitude' => $form['latitude'] ?? null,
            'longitude' => $form['longitude'] ?? null,
            'location_accuracy_m' => isset($form['location_accuracy_m']) ? (int) round($form['location_accuracy_m']) : null,
            'occurred_at' => now(),
        ]);

        foreach ($paths['photos'] as $path) {
            $handover->photos()->create(['path' => $path]);
        }

        return $handover->load('photos', 'officer');
    }

    /**
     * Stores the signatures and photos, then runs the database work in a transaction. If that
     * fails (e.g. the vehicle was just handed out by someone else), the stored files are removed.
     */
    private function withStoredFiles(array $form, callable $callback): Handover
    {
        $directory = 'handovers/'.now()->format('Y/m');
        $paths = ['photos' => []];

        try {
            $paths['officer_signature'] = $this->files->storeSignature($form['officer_signature'], $directory);
            $paths['signer_signature'] = $this->files->storeSignature($form['signer_signature'], $directory);
            foreach ($form['photos'] ?? [] as $photo) {
                $paths['photos'][] = $this->files->storePhoto($photo, $directory);
            }

            return DB::transaction(fn () => $callback($paths));
        } catch (\Throwable $e) {
            $this->files->delete(array_merge(
                array_filter([$paths['officer_signature'] ?? null, $paths['signer_signature'] ?? null]),
                $paths['photos'],
            ));

            throw $e;
        }
    }
}
