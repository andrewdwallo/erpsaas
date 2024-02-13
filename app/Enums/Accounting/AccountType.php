<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum AccountType: string implements HasLabel
{
    case CurrentAsset = 'current_asset';
    case NonCurrentAsset = 'non_current_asset';
    case ContraAsset = 'contra_asset';
    case CurrentLiability = 'current_liability';
    case NonCurrentLiability = 'non_current_liability';
    case ContraLiability = 'contra_liability';
    case Equity = 'equity';
    case ContraEquity = 'contra_equity';
    case OperatingRevenue = 'operating_revenue';
    case NonOperatingRevenue = 'non_operating_revenue';
    case ContraRevenue = 'contra_revenue';
    case UncategorizedRevenue = 'uncategorized_revenue';
    case OperatingExpense = 'operating_expense';
    case NonOperatingExpense = 'non_operating_expense';
    case ContraExpense = 'contra_expense';
    case UncategorizedExpense = 'uncategorized_expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset => 'Current Asset',
            self::NonCurrentAsset => 'Non-Current Asset',
            self::ContraAsset => 'Contra Asset',
            self::CurrentLiability => 'Current Liability',
            self::NonCurrentLiability => 'Non-Current Liability',
            self::ContraLiability => 'Contra Liability',
            self::Equity => 'Equity',
            self::ContraEquity => 'Contra Equity',
            self::OperatingRevenue => 'Operating Revenue',
            self::NonOperatingRevenue => 'Non-Operating Revenue',
            self::ContraRevenue => 'Contra Revenue',
            self::UncategorizedRevenue => 'Uncategorized Revenue',
            self::OperatingExpense => 'Operating Expense',
            self::NonOperatingExpense => 'Non-Operating Expense',
            self::ContraExpense => 'Contra Expense',
            self::UncategorizedExpense => 'Uncategorized Expense',
        };
    }

    public function getCategory(): AccountCategory
    {
        return match ($this) {
            self::CurrentAsset, self::NonCurrentAsset, self::ContraAsset => AccountCategory::Asset,
            self::CurrentLiability, self::NonCurrentLiability, self::ContraLiability => AccountCategory::Liability,
            self::Equity, self::ContraEquity => AccountCategory::Equity,
            self::OperatingRevenue, self::NonOperatingRevenue, self::ContraRevenue, self::UncategorizedRevenue => AccountCategory::Revenue,
            self::OperatingExpense, self::NonOperatingExpense, self::ContraExpense, self::UncategorizedExpense => AccountCategory::Expense,
        };
    }
}
