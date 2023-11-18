<?php

namespace App\Enums;

use App\Enums\Concerns\Utilities;
use Filament\Support\Contracts\HasLabel;

enum RecordsPerPage: int implements HasLabel
{
    use Utilities;
    case Five = 5;
    case Ten = 10;
    case TwentyFive = 25;
    case Fifty = 50;
    case OneHundred = 100;

    public const DEFAULT = self::Ten->value;

    public function getLabel(): ?string
    {
        return (string) $this->value;
    }
}
