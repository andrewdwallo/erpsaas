<?php

namespace Database\Factories;

use App\Models\Setting\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'color' => $this->faker->hexColor,
        ];
    }

    /**
     * Indicate that the category is of income type.
     *
     * @return Factory<Category>
     */
    public function income(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'income',
            ];
        });
    }

    /**
     * Indicate that the category is of expense type.
     *
     * @return Factory<Category>
     */
    public function expense(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'expense',
            ];
        });
    }
}
