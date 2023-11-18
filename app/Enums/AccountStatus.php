<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AccountStatus: string implements HasColor, HasIcon, HasLabel
{
    case Open = 'open';
    case Active = 'active';
    case Inactive = 'inactive';
    case Restricted = 'restricted';
    case Closed = 'closed';

    public const DEFAULT = self::Open->value;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Open => 'primary',
            self::Active => 'success',
            self::Inactive => 'gray',
            self::Restricted => 'warning',
            self::Closed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Open => 'heroicon-o-currency-dollar',
            self::Active => 'heroicon-o-clock',
            self::Inactive => 'heroicon-o-status-offline',
            self::Restricted => 'heroicon-o-exclamation',
            self::Closed => 'heroicon-o-x-circle',
        };
    }
}
