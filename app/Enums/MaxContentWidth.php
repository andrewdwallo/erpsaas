<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MaxContentWidth: string implements HasLabel
{
    case FOUR_XL = '4xl';
    case FIVE_XL = '5xl';
    case SIX_XL = '6xl';
    case SEVEN_XL = '7xl';
    case SCREEN_LG = 'screen-lg';
    case SCREEN_XL = 'screen-xl';
    case SCREEN_2XL = 'screen-2xl';
    case FULL = 'full';

    public const DEFAULT = self::SEVEN_XL->value;

    public function getLabel(): ?string
    {
        $label = match ($this) {
            self::FOUR_XL => '4X Large',
            self::FIVE_XL => '5X Large',
            self::SIX_XL => '6X Large',
            self::SEVEN_XL => '7X Large',
            self::SCREEN_LG => 'Screen Large',
            self::SCREEN_XL => 'Screen Extra Large',
            self::SCREEN_2XL => 'Screen 2X Large',
            self::FULL => 'Full',
        };

        return translate($label);
    }
}
