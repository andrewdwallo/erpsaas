<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasColor, HasIcon, HasLabel
{
    case Sales = 'sales';
    case Purchase = 'purchase';
    case None = 'none';

    public const DEFAULT = self::Sales->value;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Sales => 'success',
            self::Purchase => 'warning',
            self::None => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Sales => 'heroicon-o-currency-dollar',
            self::Purchase => 'heroicon-o-shopping-bag',
            self::None => 'heroicon-o-x-circle',
        };
    }
}
