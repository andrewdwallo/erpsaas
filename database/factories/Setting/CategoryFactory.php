<?php

namespace Database\Factories\Setting;

use App\Enums\CategoryType;
use App\Models\Setting\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
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
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['income', 'expense']),
            'color' => $this->faker->hexColor,
            'enabled' => false,
        ];
    }

    /**
     * Indicate that the category is of income type.
     */
    public function incomeCategory(string $name): self
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
                'type' => CategoryType::Income->value,
            ];
        });
    }

    /**
     * Indicate that the category is of expense type.
     */
    public function expenseCategory(string $name): self
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
                'type' => CategoryType::Expense->value,
            ];
        });
    }
}
