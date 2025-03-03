<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum TimeFormat: string implements HasLabel
{
    // 12-Hour Formats
    case G12_CAP = 'g:i A'; // 5:30 AM
    case G12_LOW = 'g:i a'; // 5:30 am
    case H12_CAP = 'h:i A'; // 05:30 AM
    case H12_LOW = 'h:i a'; // 05:30 am

    // 24-Hour Formats
    case G24 = 'G:i'; // 5:30
    case H24 = 'H:i'; // 05:30

    public const DEFAULT = self::G12_CAP->value;

    public function getLabel(): ?string
    {
        return Carbon::createFromTime(5, 30)->translatedFormat($this->value);
    }
}
