<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'plate_number' => fake()->unique()->bothify('???###??'),
            'vehicle_type' => 'car',
            'make' => 'Toyota',
            'model' => fake()->randomElement(['Corolla', 'Camry', 'Hiace', 'Hilux']),
            'year' => fake()->numberBetween(2015, 2025),
            'colour' => fake()->safeColorName(),
            'status' => 'available',
        ];
    }
}
