<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A model of a make, tagged with the vehicle type it is (Toyota Hiace is a bus, Toyota Hilux a pickup).
 */
#[Fillable(['name', 'vehicle_type'])]
class VehicleModel extends Model
{
    protected $table = 'fleet_vehicle_models';

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicles()
    {
        return Vehicle::withTrashed()->where('make', $this->make->name)->where('model', $this->name);
    }
}
