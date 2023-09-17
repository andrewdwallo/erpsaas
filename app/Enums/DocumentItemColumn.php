<?php

namespace App\Enums;

use App\Enums\Concerns\Utilities;
use Filament\Support\Contracts\HasLabel;

enum DocumentItemColumn: string implements HasLabel
{
    use Utilities;

    case Items = 'items';
    case Products = 'products';
    case Services = 'services';
    case Other = 'other';

    public const DEFAULT = self::Items->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
