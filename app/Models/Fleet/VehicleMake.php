<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vehicle manufacturer offered in the vehicle form. Vehicles store the name, not the id.
 */
#[Fillable(['name'])]
class VehicleMake extends Model
{
    protected $table = 'fleet_vehicle_makes';

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id')->orderBy('name');
    }

    public function vehicles()
    {
        return Vehicle::withTrashed()->where('make', $this->name);
    }
}
