<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum AccountCategory: string implements HasLabel
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getTypes(): array
    {
        return match ($this) {
            self::Asset => [
                AccountType::CurrentAsset,
                AccountType::NonCurrentAsset,
                AccountType::ContraAsset,
            ],
            self::Liability => [
                AccountType::CurrentLiability,
                AccountType::NonCurrentLiability,
                AccountType::ContraLiability,
            ],
            self::Equity => [
                AccountType::Equity,
                AccountType::ContraEquity,
            ],
            self::Revenue => [
                AccountType::OperatingRevenue,
                AccountType::NonOperatingRevenue,
                AccountType::ContraRevenue,
                AccountType::UncategorizedRevenue,
            ],
            self::Expense => [
                AccountType::OperatingExpense,
                AccountType::NonOperatingExpense,
                AccountType::ContraExpense,
                AccountType::UncategorizedExpense,
            ],
        };
    }
}
