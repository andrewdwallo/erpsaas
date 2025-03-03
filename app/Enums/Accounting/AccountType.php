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

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::CurrentAsset => 'Current Assets',
            self::NonCurrentAsset => 'Non-Current Assets',
            self::ContraAsset => 'Contra Assets',
            self::CurrentLiability => 'Current Liabilities',
            self::NonCurrentLiability => 'Non-Current Liabilities',
            self::ContraLiability => 'Contra Liabilities',
            self::Equity => 'Equity',
            self::ContraEquity => 'Contra Equity',
            self::OperatingRevenue => 'Operating Revenue',
            self::NonOperatingRevenue => 'Non-Operating Revenue',
            self::ContraRevenue => 'Contra Revenue',
            self::UncategorizedRevenue => 'Uncategorized Revenue',
            self::OperatingExpense => 'Operating Expenses',
            self::NonOperatingExpense => 'Non-Operating Expenses',
            self::ContraExpense => 'Contra Expenses',
            self::UncategorizedExpense => 'Uncategorized Expenses',
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

    public function isUncategorized(): bool
    {
        return match ($this) {
            self::UncategorizedRevenue, self::UncategorizedExpense => true,
            default => false,
        };
    }

    public function isNormalDebitBalance(): bool
    {
        return in_array($this, [
            self::CurrentAsset,
            self::NonCurrentAsset,
            self::ContraLiability,
            self::ContraEquity,
            self::ContraRevenue,
            self::OperatingExpense,
            self::NonOperatingExpense,
            self::UncategorizedExpense,
        ], true);
    }

    public function isNormalCreditBalance(): bool
    {
        return ! $this->isNormalDebitBalance();
    }

    /**
     * Determines if the account is a nominal account.
     *
     * In accounting, nominal accounts are temporary accounts that are closed at the end of each accounting period,
     * with their net balances transferred to Retained Earnings (a real account).
     */
    public function isNominal(): bool
    {
        return in_array($this->getCategory(), [
            AccountCategory::Revenue,
            AccountCategory::Expense,
        ], true);
    }

    /**
     * Determines if the account is a real account.
     *
     * In accounting, assets, liabilities, and equity are real accounts which are permanent accounts that retain their balances across accounting periods.
     * They are not closed at the end of each accounting period.
     */
    public function isReal(): bool
    {
        return ! $this->isNominal();
    }

    public function isContra(): bool
    {
        return in_array($this, [
            self::ContraAsset,
            self::ContraLiability,
            self::ContraEquity,
            self::ContraRevenue,
            self::ContraExpense,
        ], true);
    }
}
