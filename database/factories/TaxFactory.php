<?php

namespace Database\Factories;

use App\Models\Setting\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate' => $this->faker->randomFloat(4, 0, 20),
            'computation' => $this->faker->randomElement(Tax::getComputationTypes()),
            'scope' => $this->faker->randomElement(Tax::getTaxScopes()),
        ];
    }
}
