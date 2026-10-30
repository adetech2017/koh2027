<?php

namespace App\Support\Fleet;

use App\Models\Fleet\VehicleModel;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The model must be listed under the chosen make, for the chosen vehicle type.
 * On a partial update the make or type may not be in the request, so the saved ones are the fallback.
 */
class VehicleModelRule implements DataAwareRule, ValidationRule
{
    private array $data = [];

    public function __construct(private ?string $savedMake = null, private ?string $savedType = null) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $make = $this->data['make'] ?? $this->savedMake;
        $type = $this->data['vehicle_type'] ?? $this->savedType;

        $model = VehicleModel::query()
            ->whereHas('make', fn ($q) => $q->where('name', $make))
            ->where('name', $value)
            ->first();

        if (!$model) {
            $fail("Choose a model of {$make}.");
        } elseif ($model->vehicle_type !== $type) {
            $types = config('fleet.vehicle_types');
            $fail("{$make} {$value} is listed as a ".strtolower($types[$model->vehicle_type] ?? $model->vehicle_type).', not a '.strtolower($types[$type] ?? (string) $type).'.');
        }
    }
}
