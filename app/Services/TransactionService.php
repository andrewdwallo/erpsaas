<?php

namespace App\Services;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createStartingBalanceIfNeeded(Company $company, Account $account, BankAccount $bankAccount, array $transactions, float $currentBalance, string $startDate): void
    {
        if ($account->transactions()->doesntExist()) {
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
        $transactionType = $startingBalance >= 0 ? TransactionType::Deposit : TransactionType::Withdrawal;
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
        $transactionType = $transaction->amount < 0 ? TransactionType::Deposit : TransactionType::Withdrawal;
        $paymentChannel = $transaction->payment_channel;
        $chartAccount = $this->getAccountFromTransaction($company, $transaction, $transactionType);
        $postedAt = $transaction->datetime ?? Carbon::parse($transaction->date)->toDateTimeString();
        $description = $transaction->name;

        Transaction::create([
            'company_id' => $company->id,
            'account_id' => $chartAccount->id,
            'bank_account_id' => $bankAccount->id,
            'plaid_transaction_id' => $transaction->transaction_id,
            'type' => $transactionType,
            'amount' => abs($transaction->amount),
            'payment_channel' => $paymentChannel,
            'posted_at' => $postedAt,
            'description' => $description,
            'pending' => false,
            'reviewed' => false,
        ]);
    }

    public function getAccountFromTransaction(Company $company, object $transaction, TransactionType $transactionType): Account
    {
        $accountCategory = match ($transactionType) {
            TransactionType::Deposit => AccountCategory::Revenue,
            TransactionType::Withdrawal => AccountCategory::Expense,
            default => null,
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

    public function getUncategorizedAccount(Company $company, TransactionType $transactionType): Account
    {
        [$type, $name] = match ($transactionType) {
            TransactionType::Deposit => [AccountType::UncategorizedRevenue, 'Uncategorized Income'],
            TransactionType::Withdrawal => [AccountType::UncategorizedExpense, 'Uncategorized Expense'],
            default => [null, null],
        };

        return $company->accounts()
            ->where('type', $type)
            ->where('name', $name)
            ->firstOrFail();
    }

    public function createJournalEntries(Transaction $transaction): void
    {
        // Additional check to avoid duplication during replication
        if ($transaction->journalEntries()->exists() || $transaction->type->isJournal() || str_starts_with($transaction->description, '(Copy of)')) {
            return;
        }

        [$debitAccount, $creditAccount] = $this->determineAccounts($transaction);

        if ($debitAccount === null || $creditAccount === null) {
            return;
        }

        $this->createJournalEntriesForTransaction($transaction, $debitAccount, $creditAccount);
    }

    public function updateJournalEntries(Transaction $transaction): void
    {
        if ($transaction->type->isJournal() || $this->hasRelevantChanges($transaction) === false) {
            return;
        }

        $journalEntries = $transaction->journalEntries;

        $debitEntry = $journalEntries->where('type', JournalEntryType::Debit)->first();
        $creditEntry = $journalEntries->where('type', JournalEntryType::Credit)->first();

        if ($debitEntry === null || $creditEntry === null) {
            return;
        }

        [$debitAccount, $creditAccount] = $this->determineAccounts($transaction);

        if ($debitAccount === null || $creditAccount === null) {
            return;
        }

        $convertedTransactionAmount = $this->getConvertedTransactionAmount($transaction);

        DB::transaction(function () use ($debitEntry, $debitAccount, $convertedTransactionAmount, $creditEntry, $creditAccount) {
            $this->updateJournalEntryForTransaction($debitEntry, $debitAccount, $convertedTransactionAmount);
            $this->updateJournalEntryForTransaction($creditEntry, $creditAccount, $convertedTransactionAmount);
        });
    }

    public function deleteJournalEntries(Transaction $transaction): void
    {
        DB::transaction(static function () use ($transaction) {
            $transaction->journalEntries()->each(fn (JournalEntry $entry) => $entry->delete());
        });
    }

    private function determineAccounts(Transaction $transaction): array
    {
        $chartAccount = $transaction->account;
        $bankAccount = $transaction->bankAccount?->account;

        if ($transaction->type->isTransfer()) {
            // Essentially a withdrawal from the bank account and a deposit to the chart account (which is a bank account)
            // Credit: bankAccount (source of funds, money is being withdrawn)
            // Debit: chartAccount (destination of funds, money is being deposited)
            return [$chartAccount, $bankAccount];
        }

        $debitAccount = $transaction->type->isWithdrawal() ? $chartAccount : $bankAccount;
        $creditAccount = $transaction->type->isWithdrawal() ? $bankAccount : $chartAccount;

        return [$debitAccount, $creditAccount];
    }

    private function createJournalEntriesForTransaction(Transaction $transaction, Account $debitAccount, Account $creditAccount): void
    {
        $convertedTransactionAmount = $this->getConvertedTransactionAmount($transaction);

        DB::transaction(function () use ($debitAccount, $transaction, $convertedTransactionAmount, $creditAccount) {
            $debitAccount->journalEntries()->create([
                'company_id' => $transaction->company_id,
                'transaction_id' => $transaction->id,
                'type' => JournalEntryType::Debit,
                'amount' => $convertedTransactionAmount,
                'description' => $transaction->description,
                'created_by' => $transaction->created_by,
                'updated_by' => $transaction->updated_by,
            ]);

            $creditAccount->journalEntries()->create([
                'company_id' => $transaction->company_id,
                'transaction_id' => $transaction->id,
                'type' => JournalEntryType::Credit,
                'amount' => $convertedTransactionAmount,
                'description' => $transaction->description,
                'created_by' => $transaction->created_by,
                'updated_by' => $transaction->updated_by,
            ]);
        });
    }

    private function getConvertedTransactionAmount(Transaction $transaction): string
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
        $bankAccountCurrency = $transaction->bankAccount->account->currency_code;

        if ($bankAccountCurrency !== $defaultCurrency) {
            return $this->convertToDefaultCurrency($transaction->amount, $bankAccountCurrency, $defaultCurrency);
        }

        return $transaction->amount;
    }

    private function convertToDefaultCurrency(string $amount, string $fromCurrency, string $toCurrency): string
    {
        $amountInCents = CurrencyConverter::prepareForAccessor($amount, $fromCurrency);

        $convertedAmountInCents = CurrencyConverter::convertBalance($amountInCents, $fromCurrency, $toCurrency);

        return CurrencyConverter::prepareForMutator($convertedAmountInCents, $toCurrency);
    }

    private function hasRelevantChanges(Transaction $transaction): bool
    {
        return $transaction->wasChanged(['amount', 'account_id', 'bank_account_id', 'type']);
    }

    private function updateJournalEntryForTransaction(JournalEntry $journalEntry, Account $account, string $convertedTransactionAmount): void
    {
        DB::transaction(static function () use ($journalEntry, $account, $convertedTransactionAmount) {
            $journalEntry->update([
                'account_id' => $account->id,
                'amount' => $convertedTransactionAmount,
            ]);
        });
    }
}
