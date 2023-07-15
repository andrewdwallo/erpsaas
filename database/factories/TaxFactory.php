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
        // Common fields
        return [
            'description' => $this->faker->sentence,
            'rate' => $this->faker->randomFloat(4, 0, 20),
            'computation' => $this->faker->randomElement(Tax::getComputationTypes()),
            'scope' => $this->faker->randomElement(Tax::getTaxScopes()),
            'enabled' => true,
        ];
    }

    public function salesTax(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'State Sales Tax',
                'type' => 'sales',
            ];
        });
    }

    public function purchaseTax(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'State Purchase Tax',
                'type' => 'purchase',
            ];
        });
    }
}
