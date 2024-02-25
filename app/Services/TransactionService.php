<?php

namespace App\Services;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Models\Setting\Category;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Support\Carbon;

class TransactionService
{
    public function createStartingBalanceIfNeeded(Company $company, Account $account, ConnectedBankAccount $connectedBankAccount, array $transactions, float $currentBalance, string $startDate): void
    {
        if ($account->transactions()->withoutGlobalScope(CurrentCompanyScope::class)->doesntExist()) {
            $accountSign = $account->category === AccountCategory::Asset ? 1 : -1;

            $sumOfTransactions = collect($transactions)->reduce(static function ($carry, $transaction) {
                return bcadd($carry, (string) -$transaction->amount, 2);
            }, '0.00');

            $adjustedBalance = (string) ($currentBalance * $accountSign);

            $startingBalance = bcsub($adjustedBalance, $sumOfTransactions, 2);

            $this->createStartingBalanceTransaction($company, $account, $connectedBankAccount, (float) $startingBalance, $startDate);
        }
    }

    public function storeTransactions(Company $company, Account $account, ConnectedBankAccount $connectedBankAccount, array $transactions): void
    {
        foreach ($transactions as $transaction) {
            $this->storeTransaction($company, $account, $connectedBankAccount, $transaction);
        }
    }

    public function createStartingBalanceTransaction(Company $company, Account $account, ConnectedBankAccount $connectedBankAccount, float $startingBalance, string $startDate): void
    {
        [$transactionType, $method] = $startingBalance >= 0 ? ['income', 'deposit'] : ['expense', 'withdrawal'];
        $category = $this->getUncategorizedCategory($company, $transactionType);
        $chartAccount = $account->where('category', AccountCategory::Equity)->where('name', 'Owner\'s Equity')->first();

        $transactionRecord = $account->transactions()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'bank_account_id' => $connectedBankAccount->bank_account_id,
            'type' => $transactionType,
            'amount' => abs($startingBalance),
            'method' => $method,
            'payment_channel' => 'other',
            'posted_at' => $startDate,
            'description' => 'Starting Balance',
            'pending' => false,
            'reviewed' => true,
        ]);

        $this->createJournalEntries($company, $account, $transactionRecord, $chartAccount);
    }

    public function storeTransaction(Company $company, Account $account, ConnectedBankAccount $connectedBankAccount, object $transaction): void
    {
        [$transactionType, $method] = $transaction->amount < 0 ? ['income', 'deposit'] : ['expense', 'withdrawal'];
        $paymentChannel = $transaction->payment_channel;
        $category = $this->getCategoryFromTransaction($company, $transaction, $transactionType);
        $chartAccount = $category->account ?? $this->getChartFromTransaction($company, $transactionType);
        $postedAt = $transaction->datetime ?? Carbon::parse($transaction->date)->toDateTimeString();
        $description = $transaction->name;

        $transactionRecord = $account->transactions()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'bank_account_id' => $connectedBankAccount->bank_account_id,
            'type' => $transactionType,
            'amount' => abs($transaction->amount),
            'method' => $method,
            'payment_channel' => $paymentChannel,
            'posted_at' => $postedAt,
            'description' => $description,
            'pending' => $transaction->pending,
            'reviewed' => false,
        ]);

        $this->createJournalEntries($company, $account, $transactionRecord, $chartAccount);
    }

    public function createJournalEntries(Company $company, Account $account, Transaction $transaction, Account $chartAccount): void
    {
        $debitAccount = $transaction->type === 'expense' ? $chartAccount : $account;
        $creditAccount = $transaction->type === 'expense' ? $account : $chartAccount;

        $amount = $transaction->amount;

        $debitAccount->journalEntries()->create([
            'company_id' => $company->id,
            'transaction_id' => $transaction->id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $transaction->description,
        ]);

        $creditAccount->journalEntries()->create([
            'company_id' => $company->id,
            'transaction_id' => $transaction->id,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $transaction->description,
        ]);
    }

    public function getCategoryFromTransaction(Company $company, object $transaction, string $transactionType): Category
    {
        $companyCategories = $company->categories()
            ->where('type', $transactionType)
            ->whereNotIn('name', ['Other Income', 'Other Expense'])
            ->get();

        $bestMatchName = $this->findBestCategoryMatch($transaction, $companyCategories->pluck('name')->toArray());

        if ($bestMatchName === null) {
            return $this->getUncategorizedCategory($company, $transactionType);
        }

        $category = $companyCategories->firstWhere('name', $bestMatchName);

        return $category ?: $this->getUncategorizedCategory($company, $transactionType);
    }

    private function findBestCategoryMatch(object $transaction, array $userCategories): ?string
    {
        $acceptableConfidenceLevels = ['VERY_HIGH', 'HIGH'];
        $similarityThreshold = 0.7;
        $plaidDetail = $transaction->personal_finance_category->detailed ?? null;
        $plaidPrimary = $transaction->personal_finance_category->primary ?? null;
        $bestMatchName = null;
        $bestMatchPercent = 0.0;

        foreach ([$plaidDetail, $plaidPrimary] as $plaidCategory) {
            if ($plaidCategory !== null && in_array($transaction->personal_finance_category->confidence_level, $acceptableConfidenceLevels, true)) {
                $currentMatchPercent = 0.0;
                $matchedName = $this->closestCategory($plaidCategory, $userCategories, $currentMatchPercent);
                if ($currentMatchPercent >= $similarityThreshold && $currentMatchPercent > $bestMatchPercent) {
                    $bestMatchPercent = $currentMatchPercent;
                    $bestMatchName = $matchedName;
                }
            }
        }

        return $bestMatchName;
    }

    public function closestCategory(string $input, array $categories, ?float &$percent): ?string
    {
        $inputNormalized = strtolower(str_replace('_', ' ', $input));
        $originalToNormalized = [];
        foreach ($categories as $originalCategory) {
            $normalizedCategory = strtolower(str_replace('_', ' ', $originalCategory));
            $originalToNormalized[$normalizedCategory] = $originalCategory;
        }

        $shortest = -1;
        $closestNormalized = null;
        foreach ($originalToNormalized as $normalizedCategory => $originalCategory) {
            $lev = levenshtein($inputNormalized, $normalizedCategory);
            if ($lev === 0 || $lev < $shortest || $shortest < 0) {
                $closestNormalized = $normalizedCategory;
                $shortest = $lev;
            }
        }

        if ($closestNormalized !== null) {
            $percent = 1.0 - ($shortest / max(strlen($inputNormalized), strlen($closestNormalized)));

            return $originalToNormalized[$closestNormalized]; // return the original category name
        }

        $percent = 0.0;

        return null;
    }

    public function getUncategorizedCategory(Company $company, string $transactionType): Category
    {
        $name = match ($transactionType) {
            'income' => 'Other Income',
            'expense' => 'Other Expense',
        };

        return $company->categories()
            ->where('type', $transactionType)
            ->where('name', $name)
            ->firstOrFail();
    }

    public function getChartFromTransaction(Company $company, string $transactionType): Account
    {
        [$type, $name] = match ($transactionType) {
            'income' => [AccountType::UncategorizedRevenue, 'Uncategorized Income'],
            'expense' => [AccountType::UncategorizedExpense, 'Uncategorized Expense'],
        };

        return $company->accounts()
            ->where('type', $type)
            ->where('name', $name)
            ->firstOrFail();
    }
}
