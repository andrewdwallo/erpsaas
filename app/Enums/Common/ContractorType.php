<?php

namespace App\Enums\Common;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum ContractorType: string implements HasLabel
{
    use ParsesEnum;

    case Individual = 'individual';
    case Business = 'business';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
