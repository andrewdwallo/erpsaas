<?php

namespace Database\Factories;

use App\Models\Setting\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 year');
        $endDate = $this->faker->dateTimeBetween($startDate, strtotime('+1 year'));

        return [
            'description' => $this->faker->sentence,
            'rate' => $this->faker->randomFloat(4, 0, 20),
            'computation' => $this->faker->randomElement(Discount::getComputationTypes()),
            'scope' => $this->faker->randomElement(Discount::getDiscountScopes()),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }
}
