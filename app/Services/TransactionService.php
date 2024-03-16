<?php

namespace App\Services;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Carbon;

class TransactionService
{
    public function createStartingBalanceIfNeeded(Company $company, Account $account, BankAccount $bankAccount, array $transactions, float $currentBalance, string $startDate): void
    {
        if ($account->transactions()->withoutGlobalScope(CurrentCompanyScope::class)->doesntExist()) {
            $accountSign = $account->category === AccountCategory::Asset ? 1 : -1;

            $sumOfTransactions = collect($transactions)->reduce(static function ($carry, $transaction) {
                return bcadd($carry, (string) -$transaction->amount, 2);
            }, '0.00');

            $adjustedBalance = (string) ($currentBalance * $accountSign);

            $startingBalance = bcsub($adjustedBalance, $sumOfTransactions, 2);

            $this->createStartingBalanceTransaction($company, $account, $bankAccount, (float) $startingBalance, $startDate);
        }
    }

    public function storeTransactions(Company $company, BankAccount $bankAccount, array $transactions): void
    {
        foreach ($transactions as $transaction) {
            $this->storeTransaction($company, $bankAccount, $transaction);
        }
    }

    public function createStartingBalanceTransaction(Company $company, Account $account, BankAccount $bankAccount, float $startingBalance, string $startDate): void
    {
        $transactionType = $startingBalance >= 0 ? 'deposit' : 'withdrawal';
        $chartAccount = $account->where('category', AccountCategory::Equity)->where('name', 'Owner\'s Equity')->first();
        $postedAt = Carbon::parse($startDate)->subDay()->toDateTimeString();

        Transaction::create([
            'company_id' => $company->id,
            'account_id' => $chartAccount->id,
            'bank_account_id' => $bankAccount->id,
            'type' => $transactionType,
            'amount' => abs($startingBalance),
            'payment_channel' => 'other',
            'posted_at' => $postedAt,
            'description' => 'Starting Balance',
            'pending' => false,
            'reviewed' => false,
        ]);
    }

    public function storeTransaction(Company $company, BankAccount $bankAccount, object $transaction): void
    {
        $transactionType = $transaction->amount < 0 ? 'deposit' : 'withdrawal';
        $paymentChannel = $transaction->payment_channel;
        $chartAccount = $this->getAccountFromTransaction($company, $transaction, $transactionType);
        $postedAt = $transaction->datetime ?? Carbon::parse($transaction->date)->toDateTimeString();
        $description = $transaction->name;

        Transaction::create([
            'company_id' => $company->id,
            'account_id' => $chartAccount->id,
            'bank_account_id' => $bankAccount->id,
            'type' => $transactionType,
            'amount' => abs($transaction->amount),
            'payment_channel' => $paymentChannel,
            'posted_at' => $postedAt,
            'description' => $description,
            'pending' => false,
            'reviewed' => false,
        ]);
    }

    public function getAccountFromTransaction(Company $company, object $transaction, string $transactionType): Account
    {
        $accountCategory = match ($transactionType) {
            'deposit' => AccountCategory::Revenue,
            'withdrawal' => AccountCategory::Expense,
        };

        $accounts = $company->accounts()
            ->where('category', $accountCategory)
            ->whereNotIn('type', [AccountType::UncategorizedRevenue, AccountType::UncategorizedExpense])
            ->get();

        $bestMatchName = $this->findBestAccountMatch($transaction, $accounts->pluck('name')->toArray());

        if ($bestMatchName === null) {
            return $this->getUncategorizedAccount($company, $transactionType);
        }

        return $accounts->firstWhere('name', $bestMatchName) ?: $this->getUncategorizedAccount($company, $transactionType);
    }

    private function findBestAccountMatch(object $transaction, array $accountNames): ?string
    {
        $acceptableConfidenceLevels = ['VERY_HIGH', 'HIGH'];
        $similarityThreshold = 70.0;
        $plaidDetail = $transaction->personal_finance_category->detailed ?? null;
        $plaidPrimary = $transaction->personal_finance_category->primary ?? null;
        $bestMatchName = null;
        $bestMatchPercent = 0.0;

        foreach ([$plaidDetail, $plaidPrimary] as $plaidCategory) {
            if ($plaidCategory !== null && in_array($transaction->personal_finance_category->confidence_level, $acceptableConfidenceLevels, true)) {
                foreach ($accountNames as $accountName) {
                    $normalizedPlaidCategory = strtolower(str_replace('_', ' ', $plaidCategory));
                    $normalizedAccountName = strtolower(str_replace('_', ' ', $accountName));
                    $currentMatchPercent = 0.0;
                    similar_text($normalizedPlaidCategory, $normalizedAccountName, $currentMatchPercent);
                    if ($currentMatchPercent >= $similarityThreshold && $currentMatchPercent > $bestMatchPercent) {
                        $bestMatchPercent = $currentMatchPercent;
                        $bestMatchName = $accountName; // Use and return the original account name for the best match, not the normalized one
                    }
                }
            }
        }

        return $bestMatchName;
    }

    public function getUncategorizedAccount(Company $company, string $transactionType): Account
    {
        [$type, $name] = match ($transactionType) {
            'deposit' => [AccountType::UncategorizedRevenue, 'Uncategorized Income'],
            'withdrawal' => [AccountType::UncategorizedExpense, 'Uncategorized Expense'],
        };

        return $company->accounts()
            ->where('type', $type)
            ->where('name', $name)
            ->firstOrFail();
    }
}
