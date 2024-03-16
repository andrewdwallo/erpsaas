<?php

namespace App\Services;

use Akaunting\Money\Money;
use App\DTO\AccountBalanceDTO;
use App\DTO\AccountBalanceReportDTO;
use App\DTO\AccountCategoryDTO;
use App\DTO\AccountDTO;
use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Repositories\Accounting\JournalEntryRepository;
use App\ValueObjects\BalanceValue;
use Illuminate\Database\Eloquent\Collection;

class AccountService
{
    protected JournalEntryRepository $journalEntryRepository;

    public function __construct(JournalEntryRepository $journalEntryRepository)
    {
        $this->journalEntryRepository = $journalEntryRepository;
    }

    public function getDebitBalance(Account $account, string $startDate, string $endDate): BalanceValue
    {
        $amount = $this->journalEntryRepository->sumDebitAmounts($account, $startDate, $endDate);

        return new BalanceValue($amount, $account->currency_code ?? 'USD');
    }

    public function getCreditBalance(Account $account, string $startDate, string $endDate): BalanceValue
    {
        $amount = $this->journalEntryRepository->sumCreditAmounts($account, $startDate, $endDate);

        return new BalanceValue($amount, $account->currency_code ?? 'USD');
    }

    public function getNetMovement(Account $account, string $startDate, string $endDate): BalanceValue
    {
        $debitBalance = $this->journalEntryRepository->sumDebitAmounts($account, $startDate, $endDate);
        $creditBalance = $this->journalEntryRepository->sumCreditAmounts($account, $startDate, $endDate);
        $netMovement = $this->calculateNetMovementByCategory($account->category, $debitBalance, $creditBalance);

        return new BalanceValue($netMovement, $account->currency_code ?? 'USD');
    }

    public function getStartingBalance(Account $account, string $startDate): ?BalanceValue
    {
        if (in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            return null;
        }

        $debitBalanceBefore = $this->journalEntryRepository->sumDebitAmounts($account, $startDate);
        $creditBalanceBefore = $this->journalEntryRepository->sumCreditAmounts($account, $startDate);
        $startingBalance = $this->calculateNetMovementByCategory($account->category, $debitBalanceBefore, $creditBalanceBefore);

        return new BalanceValue($startingBalance, $account->currency_code ?? 'USD');
    }

    public function getEndingBalance(Account $account, string $startDate, string $endDate): ?BalanceValue
    {
        if (in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            return null;
        }

        $startingBalance = $this->getStartingBalance($account, $startDate)?->getValue();
        $netMovement = $this->getNetMovement($account, $startDate, $endDate)->getValue();
        $endingBalance = $startingBalance + $netMovement;

        return new BalanceValue($endingBalance, $account->currency_code ?? 'USD');
    }

    public function calculateNetMovementByCategory(AccountCategory $category, int $debitBalance, int $creditBalance): int
    {
        return match ($category) {
            AccountCategory::Asset, AccountCategory::Expense => $debitBalance - $creditBalance,
            AccountCategory::Liability, AccountCategory::Equity, AccountCategory::Revenue => $creditBalance - $debitBalance,
        };
    }

    public function getBalances(Account $account, string $startDate, string $endDate): array
    {
        $debitBalance = $this->getDebitBalance($account, $startDate, $endDate)->getValue();
        $creditBalance = $this->getCreditBalance($account, $startDate, $endDate)->getValue();
        $netMovement = $this->getNetMovement($account, $startDate, $endDate)->getValue();

        $balances = [
            'debit_balance' => $debitBalance,
            'credit_balance' => $creditBalance,
            'net_movement' => $netMovement,
        ];

        if (! in_array($account->category, [AccountCategory::Expense, AccountCategory::Revenue], true)) {
            $balances['starting_balance'] = $this->getStartingBalance($account, $startDate)?->getValue();
            $balances['ending_balance'] = $this->getEndingBalance($account, $startDate, $endDate)?->getValue();
        }

        return $balances;
    }

    public function getBalancesFormatted(Account $account, string $startDate, string $endDate): AccountBalanceDTO
    {
        $balances = $this->getBalances($account, $startDate, $endDate);
        $currency = $account->currency_code ?? 'USD';

        return $this->formatBalances($balances, $currency);
    }

    public function formatBalances(array $balances, string $currency): AccountBalanceDTO
    {
        foreach ($balances as $key => $balance) {
            $balances[$key] = Money::{$currency}($balance)->format();
        }

        return new AccountBalanceDTO(
            startingBalance: $balances['starting_balance'] ?? null,
            debitBalance: $balances['debit_balance'],
            creditBalance: $balances['credit_balance'],
            netMovement: $balances['net_movement'] ?? null,
            endingBalance: $balances['ending_balance'] ?? null,
        );
    }

    public function buildAccountBalanceReport(string $startDate, string $endDate): AccountBalanceReportDTO
    {
        $allCategories = $this->getAccountCategoryOrder();

        $categoryGroupedAccounts = Account::whereHas('journalEntries')
            ->select('id', 'name', 'currency_code', 'category', 'code')
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->sortBy(static fn (Collection $groupedAccounts, string $key) => array_search($key, $allCategories, true));

        $accountCategories = [];
        $reportTotalBalances = [
            'debit_balance' => 0,
            'credit_balance' => 0,
        ];

        foreach ($allCategories as $categoryName) {
            $accountsInCategory = $categoryGroupedAccounts[$categoryName] ?? collect();
            $categorySummaryBalances = [
                'debit_balance' => 0,
                'credit_balance' => 0,
                'net_movement' => 0,
            ];

            if (! in_array($categoryName, [AccountCategory::Expense->getPluralLabel(), AccountCategory::Revenue->getPluralLabel()], true)) {
                $categorySummaryBalances['starting_balance'] = 0;
                $categorySummaryBalances['ending_balance'] = 0;
            }

            $categoryAccounts = [];

            foreach ($accountsInCategory as $account) {
                $accountBalances = $this->getBalances($account, $startDate, $endDate);

                if (array_sum($accountBalances) === 0) {
                    continue;
                }

                foreach ($accountBalances as $accountBalanceType => $accountBalance) {
                    $categorySummaryBalances[$accountBalanceType] += $accountBalance;
                }

                $formattedAccountBalances = $this->formatBalances($accountBalances, $account->currency_code ?? 'USD');

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->code,
                    $formattedAccountBalances,
                );
            }

            $reportTotalBalances['debit_balance'] += $categorySummaryBalances['debit_balance'];
            $reportTotalBalances['credit_balance'] += $categorySummaryBalances['credit_balance'];

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances, $accountsInCategory->first()->currency_code ?? 'USD');

            $accountCategories[$categoryName] = new AccountCategoryDTO(
                $categoryAccounts,
                $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances, 'USD');

        return new AccountBalanceReportDTO($accountCategories, $formattedReportTotalBalances);
    }

    public function getAccountCategoryOrder(): array
    {
        return [
            AccountCategory::Asset->getPluralLabel(),
            AccountCategory::Liability->getPluralLabel(),
            AccountCategory::Equity->getPluralLabel(),
            AccountCategory::Revenue->getPluralLabel(),
            AccountCategory::Expense->getPluralLabel(),
        ];
    }

    public function getEarliestTransactionDate(): string
    {
        $earliestDate = Transaction::oldest('posted_at')
            ->value('posted_at');

        return $earliestDate ?? now()->format('Y-m-d');
    }
}
