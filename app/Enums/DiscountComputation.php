<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountComputation: string implements HasLabel
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
