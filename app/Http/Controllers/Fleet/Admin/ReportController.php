<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\Beneficiary;
use App\Models\Fleet\Evaluation;
use App\Models\Fleet\Vehicle;
use App\Services\Fleet\FleetReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private FleetReports $reports) {}

    public function compliance(Request $request): JsonResponse
    {
        $rows = $this->reports->compliance($request->query('lga'));

        return response()->json([
            'data' => $rows,
            'summary' => [
                'vehicles_out' => $rows->count(),
                'overdue' => $rows->where('standing', 'overdue')->count(),
                'due_soon' => $rows->where('standing', 'due_soon')->count(),
                'ok' => $rows->where('standing', 'ok')->count(),
            ],
        ]);
    }

    public function mileage(Vehicle $vehicle): JsonResponse
    {
        return response()->json(['readings' => $this->reports->mileage($vehicle)]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $lagos = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->timezone('Africa/Lagos')->format('Y-m-d H:i') : '';

        [$headers, $rows] = match ($type) {
            'vehicles' => [
                ['Plate', 'Type', 'Make', 'Model', 'Year', 'Colour', 'Chassis number', 'Status', 'Beneficiary', 'Beneficiary phone'],
                Vehicle::with('currentAssignment.beneficiary')->orderBy('plate_number')->lazy()->map(fn (Vehicle $v) => [
                    $v->displayPlate(), $v->vehicle_type, $v->make, $v->model, $v->year, $v->colour, $v->chassis_number, $v->status,
                    $v->currentAssignment?->beneficiary?->full_name, $v->currentAssignment?->beneficiary?->phone,
                ]),
            ],
            'beneficiaries' => [
                ['Name', 'Phone', 'Other phone', 'Email', 'LGA', 'Ward', 'Address', 'ID type', 'ID number', 'Active', 'Current vehicle'],
                Beneficiary::with('currentAssignment.vehicle')->orderBy('full_name')->lazy()->map(fn (Beneficiary $b) => [
                    $b->full_name, $b->phone, $b->alt_phone, $b->email, $b->lga, $b->ward, $b->address, $b->id_type, $b->id_number,
                    $b->isActive() ? 'Yes' : 'No', $b->currentAssignment?->vehicle?->displayPlate(),
                ]),
            ],
            'assignments' => [
                ['Plate', 'Beneficiary', 'Phone', 'LGA', 'Status', 'Handed over', 'Returned', 'Recall', 'Return by'],
                Assignment::with('vehicle', 'beneficiary', 'recall')->latest('handed_over_at')->lazy()->map(fn (Assignment $a) => [
                    $a->vehicle->displayPlate(), $a->beneficiary->full_name, $a->beneficiary->phone, $a->beneficiary->lga,
                    $a->displayStatus(), $lagos($a->handed_over_at), $lagos($a->returned_at), $a->recall?->title, $a->recall?->return_by?->toDateString(),
                ]),
            ],
            'evaluations' => [
                ['Submitted', 'Plate', 'Beneficiary', 'LGA', 'Period', 'Captured', 'Trust', 'Flags', 'Mileage (km)', 'Fuel', 'Damage', 'Damage notes', 'Branding intact', 'Branding notes', 'Review', 'Review notes', 'Reviewed by'],
                Evaluation::with('vehicle', 'beneficiary', 'period', 'reviewer')->latest('submitted_at')->lazy()->map(fn (Evaluation $e) => [
                    $lagos($e->submitted_at), $e->vehicle->displayPlate(), $e->beneficiary->full_name, $e->beneficiary->lga, $e->period->sequence,
                    $e->source, $e->trust_level, implode(', ', $e->trust_flags ?? []), $e->mileage_km, $e->fuel_level,
                    $e->has_damage ? 'Yes' : 'No', $e->damage_notes, $e->branding_intact ? 'Yes' : 'No', $e->branding_notes,
                    $e->review_status, $e->review_notes, $e->reviewer?->name,
                ]),
            ],
            'compliance' => [
                ['Plate', 'Beneficiary', 'Phone', 'LGA', 'Standing', 'This window', 'Due', 'Approved', 'Awaiting review', 'Rejected', 'Missed', 'On-time rate', 'Last submitted'],
                $this->reports->compliance($request->query('lga'))->map(fn ($r) => [
                    $r['vehicle']['plate_number'], $r['beneficiary']['full_name'], $r['beneficiary']['phone'], $r['beneficiary']['lga'], $r['standing'],
                    $r['current']['status'] ?? '', $lagos($r['current']['ends_at'] ?? null), $r['approved'], $r['awaiting_review'], $r['rejected'], $r['missed'],
                    $r['on_time_rate'] === null ? '' : round($r['on_time_rate'] * 100).'%', $lagos($r['last_submitted_at']),
                ]),
            ],
        };

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // So Excel opens it as UTF-8 (names with accents, ₦)
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, escape: '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($cell) => $this->safeCell($cell), $row), escape: '');
            }
            fclose($out);
        }, "fleet-{$type}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Beneficiaries type some of these fields; a value starting with = + - @ would run as a formula
     * when the CSV is opened in Excel, so it's prefixed with an apostrophe.
     */
    private function safeCell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) && !is_numeric($value) ? "'".$value : $value;
    }
}
