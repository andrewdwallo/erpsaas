<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxComputation: string implements HasLabel
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case Compound = 'compound';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
