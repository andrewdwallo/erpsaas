<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentPriceColumn: string implements HasLabel
{
    case Price = 'price';
    case Rate = 'rate';
    case Other = 'other';

    public const DEFAULT = self::Price->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
