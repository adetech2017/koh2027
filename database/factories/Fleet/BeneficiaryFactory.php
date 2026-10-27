<?php

namespace Database\Factories\Fleet;

use App\Models\Fleet\Beneficiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => '+234'.fake()->unique()->numerify('803#######'),
            'lga' => fake()->randomElement(['Ikeja', 'Surulere', 'Alimosho', 'Eti-Osa', 'Kosofe']),
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
