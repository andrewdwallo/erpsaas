<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;

enum Font: string implements HasLabel
{
    case Inter = 'inter';
    case Roboto = 'roboto';
    case OpenSans = 'open_sans';
    case Poppins = 'poppins';
    case NotoSans = 'noto_sans';
    case DMSans = 'dm_sans';
    case Arial = 'arial';
    case Helvetica = 'helvetica';
    case Verdana = 'verdana';
    case Rubik = 'rubik';

    public const DEFAULT = self::Inter->value;

    public function getLabel(): ?string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
