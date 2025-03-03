<?php

namespace App\DTO;

use App\Models\Setting\DocumentDefault;

readonly class DocumentColumnLabelDTO
{
    public function __construct(
        public string $items = 'Items',
        public string $units = 'Quantity',
        public string $price = 'Price',
        public string $amount = 'Amount',
    ) {}

    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'units' => $this->units,
            'price' => $this->price,
            'amount' => $this->amount,
        ];
    }

    public static function fromModel(DocumentDefault $settings): self
    {
        return new self(
            items: $settings->resolveColumnLabel('item_name', 'Items'),
            units: $settings->resolveColumnLabel('unit_name', 'Quantity'),
            price: $settings->resolveColumnLabel('price_name', 'Price'),
            amount: $settings->resolveColumnLabel('amount_name', 'Amount'),
        );
    }

    public static function getDefaultLabels(): self
    {
        return new self;
    }
}
