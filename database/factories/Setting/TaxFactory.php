<?php

namespace Database\Factories\Setting;

use App\Enums\TaxComputation;
use App\Enums\TaxScope;
use App\Enums\TaxType;
use App\Models\Setting\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
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
            'description' => $this->faker->sentence,
            'rate' => $this->faker->biasedNumberBetween(300, 5000) * 100, // 3% - 50%
            'computation' => $this->faker->randomElement(TaxComputation::class),
            'scope' => $this->faker->randomElement(TaxScope::class),
            'enabled' => true,
        ];
    }

    public function salesTax(): self
    {
        return $this->state([
            'name' => 'State Sales Tax',
            'rate' => $this->faker->biasedNumberBetween(300, 1200) * 100, // 3% - 12%
            'type' => TaxType::Sales,
        ]);
    }

    public function purchaseTax(): self
    {
        return $this->state([
            'name' => 'State Purchase Tax',
            'rate' => $this->faker->biasedNumberBetween(300, 1200) * 100, // 3% - 12%
            'type' => TaxType::Purchase,
        ]);
    }
}
