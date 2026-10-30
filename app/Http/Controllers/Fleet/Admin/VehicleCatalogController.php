<?php

namespace App\Http\Controllers\Fleet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AuditLog;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleMake;
use App\Models\Fleet\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The makes and models the vehicle form offers. Everyone can read it; super admins maintain it.
 * Vehicles store make and model names, so renames are carried onto the vehicles that use them.
 */
class VehicleCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        // How many vehicles (deleted ones too) use each make and model, so the page can show what's in use
        $usage = Vehicle::withTrashed()->selectRaw('make, model, count(*) as total')->groupBy('make', 'model')->get();
        $byMake = $usage->groupBy('make')->map->sum('total');
        $byModel = $usage->mapWithKeys(fn ($row) => ["{$row->make}|{$row->model}" => (int) $row->total]);

        $makes = VehicleMake::with('models')->orderBy('name')->get()->map(fn (VehicleMake $make) => [
            'id' => $make->id,
            'name' => $make->name,
            'vehicle_count' => (int) ($byMake[$make->name] ?? 0),
            'models' => $make->models->map(fn (VehicleModel $model) => [
                'id' => $model->id,
                'name' => $model->name,
                'vehicle_type' => $model->vehicle_type,
                'vehicle_count' => $byModel["{$make->name}|{$model->name}"] ?? 0,
            ])->values(),
        ]);

        return response()->json(['makes' => $makes]);
    }

    public function storeMake(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60', 'unique:fleet_vehicle_makes,name']], $this->messages());

        $make = VehicleMake::create(['name' => trim($data['name'])]);
        AuditLog::record('vehicle_make.created', $make, ['name' => $make->name]);

        return response()->json(['make' => ['id' => $make->id, 'name' => $make->name]], 201);
    }

    public function updateMake(Request $request, VehicleMake $make): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('fleet_vehicle_makes', 'name')->ignore($make->id)],
        ], $this->messages());

        $old = $make->name;
        $new = trim($data['name']);

        DB::transaction(function () use ($make, $old, $new) {
            Vehicle::withTrashed()->where('make', $old)->update(['make' => $new]);
            $make->update(['name' => $new]);
        });
        AuditLog::record('vehicle_make.renamed', $make, ['from' => $old, 'to' => $new]);

        return response()->json(['make' => ['id' => $make->id, 'name' => $make->name]]);
    }

    public function destroyMake(VehicleMake $make): JsonResponse
    {
        $this->refuseIfInUse($make->vehicles()->count(), "{$make->name} is recorded on");

        $make->delete();
        AuditLog::record('vehicle_make.deleted', properties: ['name' => $make->name]);

        return response()->json(['deleted' => true]);
    }

    public function storeModel(Request $request, VehicleMake $make): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('fleet_vehicle_models', 'name')->where('make_id', $make->id)],
            'vehicle_type' => ['required', Rule::in(array_keys(config('fleet.vehicle_types')))],
        ], $this->messages($make));

        $model = $make->models()->create(['name' => trim($data['name']), 'vehicle_type' => $data['vehicle_type']]);
        AuditLog::record('vehicle_model.created', $model, ['make' => $make->name, 'name' => $model->name]);

        return response()->json(['model' => $model->only('id', 'name', 'vehicle_type')], 201);
    }

    public function updateModel(Request $request, VehicleModel $model): JsonResponse
    {
        $make = $model->make;
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:60', Rule::unique('fleet_vehicle_models', 'name')->where('make_id', $make->id)->ignore($model->id)],
            'vehicle_type' => ['sometimes', 'required', Rule::in(array_keys(config('fleet.vehicle_types')))],
        ], $this->messages($make));

        $inUse = $model->vehicles()->count();

        // Vehicles of the old type would no longer match their model
        if (isset($data['vehicle_type']) && $data['vehicle_type'] !== $model->vehicle_type && $inUse > 0) {
            throw ValidationException::withMessages([
                'vehicle_type' => "Can't change the type: {$make->name} {$model->name} is recorded on {$inUse} ".str('vehicle')->plural($inUse).'.',
            ]);
        }

        $old = $model->name;
        $new = isset($data['name']) ? trim($data['name']) : $old;

        DB::transaction(function () use ($model, $make, $data, $old, $new) {
            if ($new !== $old) {
                Vehicle::withTrashed()->where('make', $make->name)->where('model', $old)->update(['model' => $new]);
            }
            $model->update(['name' => $new] + collect($data)->only('vehicle_type')->all());
        });
        AuditLog::record('vehicle_model.updated', $model, ['make' => $make->name, 'from' => $old, 'fields' => array_keys($data)]);

        return response()->json(['model' => $model->only('id', 'name', 'vehicle_type')]);
    }

    public function destroyModel(VehicleModel $model): JsonResponse
    {
        $this->refuseIfInUse($model->vehicles()->count(), "{$model->make->name} {$model->name} is recorded on");

        $model->delete();
        AuditLog::record('vehicle_model.deleted', properties: ['make' => $model->make->name, 'name' => $model->name]);

        return response()->json(['deleted' => true]);
    }

    private function refuseIfInUse(int $count, string $what): void
    {
        if ($count > 0) {
            throw ValidationException::withMessages([
                'name' => "Can't delete: {$what} {$count} ".str('vehicle')->plural($count).'. Rename it instead, or change those vehicles first.',
            ]);
        }
    }

    private function messages(?VehicleMake $make = null): array
    {
        return [
            'name.unique' => $make ? "{$make->name} already has a model with this name." : 'This make is already on the list.',
        ];
    }
}
