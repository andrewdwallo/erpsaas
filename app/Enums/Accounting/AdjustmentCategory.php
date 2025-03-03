<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum AdjustmentCategory: string implements HasLabel
{
    use ParsesEnum;

    case Tax = 'tax';
    case Discount = 'discount';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function isTax(): bool
    {
        return $this === self::Tax;
    }

    public function isDiscount(): bool
    {
        return $this === self::Discount;
    }
}
