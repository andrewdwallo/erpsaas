<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TableSortDirection: string implements HasLabel
{
    case Ascending = 'asc';
    case Descending = 'desc';

    public const DEFAULT = self::Ascending->value;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }
}
