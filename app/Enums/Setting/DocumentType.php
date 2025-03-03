<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasLabel
{
    case Invoice = 'invoice';
    case Bill = 'bill';
    case Estimate = 'estimate';

    public const DEFAULT = self::Invoice->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
