<?php

namespace App\Enums\Common;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ContactType: string implements HasColor, HasIcon, HasLabel
{
    case Employee = 'employee';
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Supplier = 'supplier';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Employee => Color::Green,
            self::Customer => Color::Blue,
            self::Vendor => Color::Orange,
            self::Supplier => Color::Purple,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Employee => 'heroicon-o-user-group',
            self::Customer => 'heroicon-o-user',
            self::Vendor => 'heroicon-o-shopping-bag',
            self::Supplier => 'heroicon-o-truck',
        };
    }
}
