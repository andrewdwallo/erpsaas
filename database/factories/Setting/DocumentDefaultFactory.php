<?php

namespace Database\Factories\Setting;

use App\Enums\DocumentType;
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
     * The model's common default state.
     */
    private function baseState(DocumentType $type, string $prefix, string $header): array
    {
        return [
            'type' => $type->value,
            'number_prefix' => $prefix,
            'header' => $header,
            'item_name' => ['option' => 'items', 'custom' => null],
            'unit_name' => ['option' => 'quantity', 'custom' => null],
            'price_name' => ['option' => 'price', 'custom' => null],
            'amount_name' => ['option' => 'amount', 'custom' => null],
        ];
    }

    /**
     * Indicate that the model's type is invoice.
     */
    public function invoice(): self
    {
        return $this->state($this->baseState(DocumentType::Invoice, 'INV-', 'Invoice'));
    }

    /**
     * Indicate that the model's type is bill.
     */
    public function bill(): self
    {
        return $this->state($this->baseState(DocumentType::Bill, 'BILL-', 'Bill'));
    }
}
