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
            'document_number_digits' => '5',
            'document_number_next' => '1',
            'payment_terms' => '30',
            'template' => 'default',
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
                'document_number_prefix' => 'INV-',
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
                'document_number_prefix' => 'BILL-',
            ];
        });
    }
}
