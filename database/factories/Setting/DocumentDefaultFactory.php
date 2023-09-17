<?php

namespace Database\Factories\Setting;

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
            //
        ];
    }

    /**
     * Indicate that the model's type is invoice.
     *
     * @return DocumentDefaultFactory
     */
    public function invoice(): self
    {
        return $this->state([
            'type' => 'invoice',
            'number_prefix' => 'INV-',
            'header' => 'Invoice',
            'item_name' => [
                'option' => 'items',
                'custom' => null,
            ],
            'unit_name' => [
                'option' => 'quantity',
                'custom' => null,
            ],
            'price_name' => [
                'option' => 'price',
                'custom' => null,
            ],
            'amount_name' => [
                'option' => 'amount',
                'custom' => null,
            ],
        ]);
    }

    /**
     * Indicate that the model's type is bill.
     *
     * @return DocumentDefaultFactory
     */
    public function bill(): self
    {
        return $this->state([
            'type' => 'bill',
            'number_prefix' => 'BILL-',
            'header' => 'Bill',
            'item_name' => [
                'option' => 'items',
                'custom' => null,
            ],
            'unit_name' => [
                'option' => 'quantity',
                'custom' => null,
            ],
            'price_name' => [
                'option' => 'price',
                'custom' => null,
            ],
            'amount_name' => [
                'option' => 'amount',
                'custom' => null,
            ],
        ]);
    }
}
