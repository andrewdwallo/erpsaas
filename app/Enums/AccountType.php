<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case Checking = 'checking';
    case Savings = 'savings';
    case MoneyMarket = 'money_market';
    case CreditCard = 'credit_card';
    case Merchant = 'merchant';

    public const DEFAULT = self::Checking->value;

    public function getLabel(): ?string
    {
        $label = ucwords(str_replace('_', ' ', $this->value));

        return translate($label);
    }
}
