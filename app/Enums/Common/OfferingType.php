<?php

namespace App\Enums\Common;

use Filament\Support\Contracts\HasLabel;
use JaOcero\RadioDeck\Contracts\HasIcons;

enum OfferingType: string implements HasIcons, HasLabel
{
    case Product = 'product';
    case Service = 'service';

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getIcons(): ?string
    {
        return match ($this) {
            self::Product => 'heroicon-o-cube-transparent',
            self::Service => 'heroicon-o-briefcase',
        };
    }
}
