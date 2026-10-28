<?php

namespace App\Models\Fleet;

use Database\Factories\Fleet\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['plate_number', 'vehicle_type', 'make', 'model', 'year', 'colour', 'chassis_number', 'engine_number', 'branding', 'status', 'notes'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'fleet_vehicles';

    // "assigned" is set by handover and return only; admins choose between the others
    public const STATUSES = ['available', 'assigned', 'maintenance', 'retired'];

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }

    /**
     * KJA-123 xy, kja 123xy → KJA123XY, so the same plate can't be entered twice in different forms.
     */
    public static function normalizePlate(?string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $plate));
    }

    protected function plateNumber(): Attribute
    {
        return Attribute::make(set: fn ($value) => static::normalizePlate($value));
    }

    /**
     * KJA123XY → KJA 123 XY (the usual Nigerian layout); anything else is shown as stored.
     */
    public function displayPlate(): string
    {
        return preg_match('/^([A-Z]{3})(\d{2,3})([A-Z]{2})$/', $this->plate_number, $m)
            ? "{$m[1]} {$m[2]} {$m[3]}"
            : $this->plate_number;
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(Assignment::class)->where('status', 'active')->latestOfMany();
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->displayPlate(),
            'vehicle_type' => $this->vehicle_type,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'colour' => $this->colour,
            'chassis_number' => $this->chassis_number,
            'engine_number' => $this->engine_number,
            'branding' => $this->branding,
            'status' => $this->status,
            'notes' => $this->notes,
            'label' => trim("{$this->displayPlate()} · {$this->make} {$this->model}"),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
