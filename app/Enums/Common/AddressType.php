<?php

namespace App\Enums\Common;

use Filament\Support\Contracts\HasLabel;

enum AddressType: string implements HasLabel
{
    case General = 'general';
    case Billing = 'billing';
    case Shipping = 'shipping';

    public function getLabel(): string
    {
        return $this->name;
    }
}
