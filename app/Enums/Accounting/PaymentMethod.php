<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case BankPayment = 'bank_payment';
    case Cash = 'cash';
    case Check = 'check';
    case CreditCard = 'credit_card';
    case PayPal = 'paypal';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::BankPayment => 'Bank Payment',
            self::Cash => 'Cash',
            self::Check => 'Check',
            self::CreditCard => 'Credit Card',
            self::PayPal => 'PayPal',
            self::Other => 'Other',
        };
    }
}
