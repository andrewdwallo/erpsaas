<?php

namespace App\DTO;

readonly class LineItemPreviewDTO extends LineItemDTO
{
    public static function fakeItems(): array
    {
        return [
            new self(
                name: 'Item 1',
                description: 'Sample item description',
                quantity: 2,
                unitPrice: self::formatToMoney(150.00, null),
                subtotal: self::formatToMoney(300.00, null),
            ),
            new self(
                name: 'Item 2',
                description: 'Another sample item description',
                quantity: 3,
                unitPrice: self::formatToMoney(200.00, null),
                subtotal: self::formatToMoney(600.00, null),
            ),
            new self(
                name: 'Item 3',
                description: 'Yet another sample item description',
                quantity: 1,
                unitPrice: self::formatToMoney(180.00, null),
                subtotal: self::formatToMoney(180.00, null),
            ),
        ];
    }
}
