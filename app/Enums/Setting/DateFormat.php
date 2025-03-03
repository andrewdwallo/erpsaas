<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;

enum DateFormat: string implements HasLabel
{
    // Day-Month-Year Formats
    case DMY_SLASH = 'd/m/Y'; // 31/12/2021
    case DMY_DASH = 'd-m-Y'; // 31-12-2021
    case DMY_DOT = 'd.m.Y'; // 31.12.2021
    case DMY_SPACE = 'd m Y'; // 31 12 2021
    case DMY_LONG = 'd F Y'; // 31 December 2021
    case DMY_SHORT = 'd M Y'; // 31 Dec 2021

    // Month-Day-Year Formats
    case MDY_SLASH = 'm/d/Y'; // 12/31/2021
    case MDY_DASH = 'm-d-Y'; // 12-31-2021
    case MDY_DOT = 'm.d.Y'; // 12.31.2021
    case MDY_SPACE = 'm d Y'; // 12 31 2021
    case MDY_LONG_SPACE = 'F d Y'; // December 31 2021
    case MDY_LONG_COMMA = 'F j, Y'; // December 31, 2021
    case MDY_SHORT_SPACE = 'M d Y'; // Dec 31 2021
    case MDY_SHORT_COMMA = 'M j, Y'; // Dec 31, 2021

    // Year-Month-Day Formats
    case YMD_SLASH = 'Y/m/d'; // 2021/12/31
    case YMD_DASH = 'Y-m-d'; // 2021-12-31
    case YMD_DOT = 'Y.m.d'; // 2021.12.31
    case YMD_SPACE = 'Y m d'; // 2021 12 31
    case YMD_LONG = 'Y F d'; // 2021 December 31
    case YMD_SHORT = 'Y M d'; // 2021 Dec 31

    public const DEFAULT = self::MDY_SHORT_COMMA->value;

    public function getLabel(): ?string
    {
        return now()->translatedFormat($this->value);
    }
}
