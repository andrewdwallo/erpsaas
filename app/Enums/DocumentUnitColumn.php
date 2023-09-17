<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DocumentUnitColumn: string implements HasLabel
{
    case Quantity = 'quantity';
    case Hours = 'hours';
    case Other = 'other';

    public const DEFAULT = self::Quantity->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
