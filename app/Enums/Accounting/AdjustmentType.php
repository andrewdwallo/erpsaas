<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AdjustmentType: string implements HasColor, HasIcon, HasLabel
{
    use ParsesEnum;

    case Sales = 'sales';
    case Purchase = 'purchase';

    public function getLabel(): ?string
    {
        return translate($this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Sales => 'success',
            self::Purchase => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Sales => 'heroicon-o-currency-dollar',
            self::Purchase => 'heroicon-o-shopping-bag',
        };
    }

    public function isSales(): bool
    {
        return $this === self::Sales;
    }

    public function isPurchase(): bool
    {
        return $this === self::Purchase;
    }
}
