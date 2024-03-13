<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankAccountType: string implements HasLabel
{
    case Investment = 'investment';
    case Credit = 'credit';
    case Depository = 'depository';
    case Loan = 'loan';
    case Other = 'other';

    public const DEFAULT = self::Depository;

    public function getLabel(): ?string
    {
        return translate($this->name);
    }
}
