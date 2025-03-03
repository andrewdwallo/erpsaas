<?php

namespace App\Enums\Banking;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum BankAccountType: string implements HasLabel
{
    use ParsesEnum;

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

    public function getDefaultSubtype(): string
    {
        return match ($this) {
            self::Depository => 'Cash and Cash Equivalents',
            self::Credit => 'Short-Term Borrowings',
            self::Loan => 'Long-Term Borrowings',
            self::Investment => 'Long-Term Investments',
            self::Other => 'Other Current Assets',
        };
    }
}
