<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ModalWidth: string implements HasLabel
{
    case XS = 'xs';
    case SM = 'sm';
    case MD = 'md';
    case LG = 'lg';
    case XL = 'xl';
    case TWO_XL = '2xl';
    case THREE_XL = '3xl';
    case FOUR_XL = '4xl';
    case FIVE_XL = '5xl';
    case SIX_XL = '6xl';
    case SEVEN_XL = '7xl';
    case SCREEN = 'screen';

    public const DEFAULT = self::MD->value;

    public function getLabel(): ?string
    {
        $label = match ($this) {
            self::XS => 'Extra Small',
            self::SM => 'Small',
            self::MD => 'Medium',
            self::LG => 'Large',
            self::XL => 'Extra Large',
            self::TWO_XL => '2X Large',
            self::THREE_XL => '3X Large',
            self::FOUR_XL => '4X Large',
            self::FIVE_XL => '5X Large',
            self::SIX_XL => '6X Large',
            self::SEVEN_XL => '7X Large',
            self::SCREEN => 'Screen',
        };

        return translate($label);
    }
}
