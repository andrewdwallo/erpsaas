<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum IntervalModifier: string implements HasLabel
{
    case First = 'first';
    case Second = 'second';
    case Third = 'third';
    case Fourth = 'fourth';
    case Last = 'last';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
