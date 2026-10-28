<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\CsvReader;
use App\Support\Fleet\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk-adds beneficiaries or vehicles from a CSV. All or nothing: if any row has a problem,
 * nothing is imported and every problem is listed by line number.
 */
class ImportController extends Controller
{
    private const MAX_ROWS = 2000;

    private const COLUMNS = [
        'beneficiaries' => ['full_name', 'phone', 'alt_phone', 'email', 'address', 'lga', 'ward', 'id_type', 'id_number', 'notes'],
        'vehicles' => ['plate_number', 'vehicle_type', 'make', 'model', 'year', 'colour', 'chassis_number', 'engine_number', 'branding', 'notes'],
    ];

    private const EXAMPLES = [
        'beneficiaries' => ['Adaeze Okafor', '0803 123 4567', '', 'adaeze@example.com', '12 Allen Avenue', 'Ikeja', 'Ward 3', 'nin', '12345678901', ''],
        'vehicles' => ['KJA 123 XY', 'car', 'Toyota', 'Corolla', '2020', 'White', 'JTDBR32E720123456', '', 'Full wrap, KOH 2027', ''],
    ];

    public function template(string $type): StreamedResponse
    {
        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::COLUMNS[$type], escape: '');
            fputcsv($out, self::EXAMPLES[$type], escape: '');
            fclose($out);
        }, "fleet-{$type}-template.csv", ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, string $type, CsvReader $reader): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $rows = $reader->read($request->file('file'));

        if ($rows === []) {
            return response()->json(['message' => 'The file has no rows to import.'], 422);
        }
        if (count($rows) > self::MAX_ROWS) {
            return response()->json(['message' => 'Import at most '.self::MAX_ROWS.' rows at a time.'], 422);
        }

        $missing = array_diff($type === 'beneficiaries' ? ['full_name', 'phone'] : ['plate_number', 'vehicle_type', 'make', 'model'], array_keys(reset($rows)));
        if ($missing) {
            return response()->json(['message' => 'The file is missing these columns: '.implode(', ', $missing).'. Download the template to see the expected layout.'], 422);
        }

        [$records, $errors] = $type === 'beneficiaries' ? $this->checkBeneficiaries($rows) : $this->checkVehicles($rows);

        if ($errors) {
            return response()->json([
                'message' => 'Nothing was imported. Fix the rows below and upload the file again.',
                'row_errors' => $errors,
            ], 422);
        }

        $adminId = auth('fleet_admin')->id();
        DB::transaction(function () use ($type, $records, $adminId) {
            foreach ($records as $record) {
                $model = $type === 'beneficiaries' ? new Beneficiary($record) : new Vehicle($record);
                $model->forceFill(['created_by' => $adminId])->save();
            }
        });
        AuditLog::record("{$type}.imported", properties: ['count' => count($records)]);

        return response()->json(['imported' => count($records), 'message' => count($records)." {$type} imported."]);
    }

    private function checkBeneficiaries(array $rows): array
    {
        $records = $errors = $seen = [];
        $lgas = collect(config('fleet.lgas'))->keyBy(fn ($lga) => strtolower($lga));
        $idTypes = collect(config('fleet.id_types'))->mapWithKeys(fn ($label, $key) => [strtolower($label) => $key, $key => $key]);

        foreach ($rows as $line => $row) {
            $row = array_intersect_key($row, array_flip(self::COLUMNS['beneficiaries']));
            foreach (['phone', 'alt_phone'] as $field) {
                if ($row[$field] ?? null) {
                    $row[$field] = PhoneNumber::normalize($row[$field]) ?? $row[$field];
                }
            }
            // Accept "ikeja" for Ikeja and "Voter's card" for voters_card, as people type them in Excel
            if ($row['lga'] ?? null) {
                $row['lga'] = $lgas[strtolower($row['lga'])] ?? $row['lga'];
            }
            if ($row['id_type'] ?? null) {
                $row['id_type'] = $idTypes[strtolower($row['id_type'])] ?? $row['id_type'];
            }

            $messages = Validator::make($row, BeneficiaryController::rules(), BeneficiaryController::messages())->errors()->all();

            if (isset($row['phone']) && isset($seen[$row['phone']])) {
                $messages[] = "Same phone number as line {$seen[$row['phone']]}.";
            }
            $seen[$row['phone'] ?? ''] ??= $line;

            if ($messages) {
                $errors[] = ['line' => $line, 'errors' => $messages];
            } else {
                $records[] = $row;
            }
        }

        return [$records, $errors];
    }

    private function checkVehicles(array $rows): array
    {
        $records = $errors = $seenPlates = $seenChassis = [];
        $types = collect(config('fleet.vehicle_types'))->mapWithKeys(fn ($label, $key) => [strtolower($label) => $key, $key => $key]);

        foreach ($rows as $line => $row) {
            $row = array_intersect_key($row, array_flip(self::COLUMNS['vehicles']));
            $row['plate_number'] = Vehicle::normalizePlate($row['plate_number'] ?? '') ?: null;
            if ($row['chassis_number'] ?? null) {
                $row['chassis_number'] = strtoupper($row['chassis_number']);
            }
            if ($row['vehicle_type'] ?? null) {
                $row['vehicle_type'] = $types[strtolower($row['vehicle_type'])] ?? strtolower($row['vehicle_type']);
            }

            $messages = Validator::make($row, VehicleController::rules(), VehicleController::messages())->errors()->all();

            if ($row['plate_number'] && isset($seenPlates[$row['plate_number']])) {
                $messages[] = "Same plate number as line {$seenPlates[$row['plate_number']]}.";
            }
            if (($row['chassis_number'] ?? null) && isset($seenChassis[$row['chassis_number']])) {
                $messages[] = "Same chassis number as line {$seenChassis[$row['chassis_number']]}.";
            }
            $seenPlates[$row['plate_number'] ?? ''] ??= $line;
            $seenChassis[$row['chassis_number'] ?? ''] ??= $line;

            if ($messages) {
                $errors[] = ['line' => $line, 'errors' => $messages];
            } else {
                $records[] = $row;
            }
        }

        return [$records, $errors];
    }
}
