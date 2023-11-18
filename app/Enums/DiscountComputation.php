<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountComputation: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public const DEFAULT = self::Percentage->value;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }
}
