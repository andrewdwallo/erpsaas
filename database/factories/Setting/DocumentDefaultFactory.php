<?php

namespace Database\Factories\Setting;

use App\Enums\Accounting\DocumentType;
use App\Enums\Setting\Font;
use App\Enums\Setting\Template;
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
            'company_id' => 1,
            'payment_terms' => 'due_upon_receipt',
        ];
    }

    /**
     * The model's common default state.
     */
    private function baseState(DocumentType $type): array
    {
        $state = [
            'type' => $type,
            'number_prefix' => $type->getDefaultPrefix(),
            'item_name' => ['option' => 'items', 'custom' => null],
            'unit_name' => ['option' => 'quantity', 'custom' => null],
            'price_name' => ['option' => 'price', 'custom' => null],
            'amount_name' => ['option' => 'amount', 'custom' => null],
        ];

        if ($type !== DocumentType::Bill) {
            $state = [...$state,
                'header' => $type->getLabel(),
                'show_logo' => false,
                'accent_color' => '#4F46E5',
                'font' => Font::Inter,
                'template' => Template::Default,
            ];
        }

        return $state;
    }

    /**
     * Indicate that the model's type is invoice.
     */
    public function invoice(): self
    {
        return $this->state($this->baseState(DocumentType::Invoice));
    }

    /**
     * Indicate that the model's type is bill.
     */
    public function bill(): self
    {
        return $this->state($this->baseState(DocumentType::Bill));
    }

    /**
     * Indicate that the model's type is estimate.
     */
    public function estimate(): self
    {
        return $this->state($this->baseState(DocumentType::Estimate));
    }
}
