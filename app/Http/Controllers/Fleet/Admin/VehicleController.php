<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Assignment;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $vehicles = Vehicle::query()
            ->with('currentAssignment.beneficiary')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('plate_number', 'like', '%'.Vehicle::normalizePlate($search).'%')
                ->orWhere('make', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('chassis_number', $search)))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('vehicle_type'), fn ($q, $type) => $q->where('vehicle_type', $type))
            ->orderBy('plate_number')
            ->paginate(min((int) $request->query('per_page', 25), 100))
            ->through(fn (Vehicle $vehicle) => $vehicle->toApi() + [
                'beneficiary' => $vehicle->currentAssignment?->beneficiary?->toApi(),
                'assignment_id' => $vehicle->currentAssignment?->id,
            ]);

        return response()->json($vehicles);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $assignments = $vehicle->assignments()
            ->with('beneficiary', 'recall', 'handoverForm', 'returnForm')
            ->latest('handed_over_at')
            ->get()
            ->map(fn (Assignment $assignment) => $assignment->toApi());

        return response()->json([
            'vehicle' => $vehicle->toApi(),
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalisePlate($request);
        $data = $request->validate(static::rules(), static::messages());

        $vehicle = Vehicle::create($data);
        $vehicle->forceFill(['created_by' => auth('fleet_admin')->id()])->save();
        AuditLog::record('vehicle.created', $vehicle);

        return response()->json(['vehicle' => $vehicle->toApi()], 201);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->normalisePlate($request);
        $data = $request->validate(static::partial(static::rules($vehicle)) + [
            // "assigned" comes from the handover form, never set by hand
            'status' => ['sometimes', Rule::in(['available', 'maintenance', 'retired'])],
        ], static::messages());

        if (isset($data['status']) && $data['status'] !== $vehicle->status && $vehicle->status === 'assigned') {
            throw ValidationException::withMessages(['status' => 'This vehicle is with a beneficiary. Record its return first.']);
        }

        $vehicle->update($data);
        AuditLog::record('vehicle.updated', $vehicle, ['fields' => array_keys($data)]);

        return response()->json(['vehicle' => $vehicle->toApi()]);
    }

    /**
     * PATCH sends only the fields being changed, so each rule applies only when its field is present.
     */
    private static function partial(array $rules): array
    {
        return array_map(fn (array $fieldRules) => ['sometimes', ...$fieldRules], $rules);
    }

    /**
     * Shared with the CSV import. The plate number must already be normalised.
     */
    public static function rules(?Vehicle $ignore = null): array
    {
        return [
            'plate_number' => ['required', 'string', 'min:4', 'max:20', Rule::unique('fleet_vehicles', 'plate_number')->ignore($ignore?->id)],
            'vehicle_type' => ['required', Rule::in(array_keys(config('fleet.vehicle_types')))],
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'colour' => ['nullable', 'string', 'max:40'],
            'chassis_number' => ['nullable', 'string', 'max:40', Rule::unique('fleet_vehicles', 'chassis_number')->ignore($ignore?->id)],
            'engine_number' => ['nullable', 'string', 'max:40'],
            'branding' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function messages(): array
    {
        return [
            'plate_number.unique' => 'A vehicle with this plate number is already registered.',
            'chassis_number.unique' => 'A vehicle with this chassis number is already registered.',
        ];
    }

    private function normalisePlate(Request $request): void
    {
        if ($request->filled('plate_number')) {
            $request->merge(['plate_number' => Vehicle::normalizePlate($request->input('plate_number'))]);
        }
        if ($request->filled('chassis_number')) {
            $request->merge(['chassis_number' => strtoupper(trim($request->input('chassis_number')))]);
        }
    }
}
