<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum EndType: string implements HasLabel
{
    use ParsesEnum;

    case Never = 'never';
    case After = 'after';
    case On = 'on';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function isNever(): bool
    {
        return $this === self::Never;
    }

    public function isAfter(): bool
    {
        return $this === self::After;
    }

    public function isOn(): bool
    {
        return $this === self::On;
    }
}
