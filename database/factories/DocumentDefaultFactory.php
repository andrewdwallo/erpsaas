<?php

namespace Database\Factories;

use App\Models\Setting\DocumentDefault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentDefault>
 */
class DocumentDefaultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DocumentDefault::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number_digits' => '5',
            'number_next' => '1',
            'payment_terms' => '30',
            'accent_color' => '#007BFF',
            'template' => 'default',
            'item_column' => 'items',
            'unit_column' => 'quantity',
            'price_column' => 'price',
            'amount_column' => 'amount',
        ];
    }

    /**
     * Indicate that the model's type is invoice.
     */
    public function invoice(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'invoice',
                'number_prefix' => 'INV-',
            ];
        });
    }

    /**
     * Indicate that the model's type is bill.
     */
    public function bill(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'bill',
                'number_prefix' => 'BILL-',
            ];
        });
    }
}
