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

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::Asset => 'Assets',
            self::Liability => 'Liabilities',
            self::Equity => 'Equity',
            self::Revenue => 'Revenue',
            self::Expense => 'Expenses',
        };
    }

    public static function fromPluralLabel(string $label): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->getPluralLabel() === $label) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Determines if the account typically has a normal debit balance.
     *
     * In accounting, assets and expenses typically have a normal debit balance.
     * A debit increases the balance of these accounts, while a credit decreases it.
     */
    public function isNormalDebitBalance(): bool
    {
        return in_array($this, [self::Asset, self::Expense], true);
    }

    /**
     * Determines if the account typically has a normal credit balance.
     *
     * In accounting, liabilities, equity, and revenue typically have a normal credit balance.
     * A credit increases the balance of these accounts, while a debit decreases it.
     */
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
        return in_array($this, [self::Revenue, self::Expense], true);
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

    public function getRelevantBalanceFields(): array
    {
        $commonFields = ['debit_balance', 'credit_balance', 'net_movement'];

        return match ($this->isReal()) {
            true => [...$commonFields, 'starting_balance', 'ending_balance'],
            false => $commonFields,
        };
    }

    public static function getOrderedCategories(): array
    {
        return [
            self::Asset,
            self::Liability,
            self::Equity,
            self::Revenue,
            self::Expense,
        ];
    }
}
