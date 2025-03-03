<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum AdjustmentComputation: string implements HasLabel
{
    use ParsesEnum;

    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function getLabel(): ?string
    {
        return translate($this->name);
    }

    public function isPercentage(): bool
    {
        return $this == self::Percentage;
    }

    public function isFixed(): bool
    {
        return $this == self::Fixed;
    }
}
