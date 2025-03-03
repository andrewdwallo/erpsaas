<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;

enum WeekStart: int implements HasLabel
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public const DEFAULT = self::Monday->value;

    public function getLabel(): ?string
    {
        return today()->isoWeekday($this->value)->dayName;
    }
}
