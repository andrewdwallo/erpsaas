<?php

namespace App\Listeners;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Events\StartTransactionImport;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Services\PlaidService;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HandleTransactionImport
{
    protected PlaidService $plaid;

    /**
     * Create the event listener.
     */
    public function __construct(PlaidService $plaid)
    {
        $this->plaid = $plaid;
    }

    /**
     * Handle the event.
     */
    public function handle(StartTransactionImport $event): void
    {
        DB::transaction(function () use ($event) {
            $this->processTransactionImport($event);
        });
    }

    public function processTransactionImport(StartTransactionImport $event): void
    {
        $company = $event->company;
        $connectedBankAccount = $event->connectedBankAccount;
        $selectedBankAccountId = $event->selectedBankAccountId;
        $startDate = $event->startDate;

        $accessToken = $connectedBankAccount->access_token;

        $bankAccount = $selectedBankAccountId === 'new'
            ? $this->processNewBankAccount($company, $connectedBankAccount, $accessToken)
            : BankAccount::find($selectedBankAccountId);

        if ($bankAccount) {
            $connectedBankAccount->update([
                'bank_account_id' => $bankAccount->id,
                'import_transactions' => true,
            ]);

            $account = $bankAccount->account;

            $this->processTransactions($startDate, $company, $connectedBankAccount, $accessToken, $account);
        }
    }

    public function processTransactions($startDate, Company $company, ConnectedBankAccount $connectedBankAccount, $accessToken, Account $account): void
    {
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::parse($startDate)->toDateString();

        $transactionsResponse = $this->plaid->getTransactions($accessToken, $startDate, $endDate, [
            'account_ids' => [$connectedBankAccount->external_account_id],
        ]);

        if (! empty($transactionsResponse->transactions)) {
            foreach ($transactionsResponse->transactions as $transaction) {
                $this->storeTransaction($company, $account, $connectedBankAccount, $transaction);
            }
        }
    }

    public function storeTransaction(Company $company, Account $account, ConnectedBankAccount $connectedBankAccount, $transaction): void
    {
        if ($account->category === AccountCategory::Asset) {
            $transactionType = $transaction->amount < 0 ? 'income' : 'expense';
        } else {
            $transactionType = $transaction->amount < 0 ? 'expense' : 'income';
        }

        $method = $transactionType === 'income' ? 'deposit' : 'withdrawal';
        $paymentChannel = $transaction->payment_channel ?? 'other';
        $category = $this->getCategoryFromTransaction($company, $transaction, $transactionType);
        $chartAccount = $category->account ?? $this->getChartFromTransaction($company, $transaction, $transactionType);

        $postedAt = $transaction->datetime ?? Carbon::parse($transaction->date)->toDateTimeString();

        $description = $transaction->original_description ?? $transaction->name;

        Log::info('Transaction description:', [
            'name' => $transaction->name,
            'description' => $description,
            'amount' => $transaction->amount,
            'detailedCategory' => $transaction->personal_finance_category->detailed,
            'primaryCategory' => $transaction->personal_finance_category->primary,
        ]);

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
        // For an expense (withdrawal) transaction, we need to credit the liability or asset account ($account), and debit the expense account ($chartAccount)
        // For an income (deposit) transaction, we need to debit the liability or asset account ($account), and credit the revenue account ($chartAccount)
        // Debiting an Asset account increases its balance. Crediting an Asset account decreases its balance.
        // Crediting a Liability account increases its balance. Debiting a Liability account decreases its balance.
        // Expense accounts should always be debited. Revenue accounts should always be credited.
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

    public function getCategoryFromTransaction(Company $company, $transaction, string $transactionType): Category
    {
        $acceptableConfidenceLevels = ['VERY_HIGH', 'HIGH'];

        $userCategories = $company->categories()->get();
        $plaidDetail = $transaction->personal_finance_category->detailed ?? null;
        $plaidPrimary = $transaction->personal_finance_category->primary ?? null;

        $category = null;

        if ($plaidDetail !== null && in_array($transaction->personal_finance_category->confidence_level, $acceptableConfidenceLevels, true)) {
            $category = $this->matchCategory($userCategories, $plaidDetail, $transactionType);
        }

        if ($plaidPrimary !== null && ($category === null || $this->isUncategorized($category))) {
            $category = $this->matchCategory($userCategories, $plaidPrimary, $transactionType);
        }

        return $category ?? $this->getUncategorizedCategory($company, $transaction, $transactionType);
    }

    public function isUncategorized(Category $category): bool
    {
        return Str::contains(strtolower($category->name), 'other');
    }

    public function matchCategory($userCategories, $plaidCategory, string $transactionType): ?Category
    {
        $plaidWords = explode(' ', strtolower($plaidCategory));

        $bestMatchCategory = null;
        $bestMatchScore = 0; // Higher is better

        foreach ($userCategories as $category) {
            if (strtolower($category->type->value) !== strtolower($transactionType)) {
                continue;
            }

            $categoryWords = explode(' ', strtolower($category->name));
            $matchScore = count(array_intersect($plaidWords, $categoryWords));

            if ($matchScore > $bestMatchScore) {
                $bestMatchScore = $matchScore;
                $bestMatchCategory = $category;
            }
        }

        return $bestMatchCategory;
    }

    public function getUncategorizedCategory(Company $company, $transaction, string $transactionType): Category
    {
        $uncategorizedCategoryName = 'Other ' . ucfirst($transactionType);
        $uncategorizedCategory = $company->categories()->where('type', $transactionType)->where('name', $uncategorizedCategoryName)->first();

        if ($uncategorizedCategory === null) {
            $uncategorizedCategory = $company->categories()->where('type', $transactionType)->where('name', 'Other')->first();

            if ($uncategorizedCategory === null) {
                $uncategorizedCategory = $company->categories()->where('name', 'Other')->first();
            }
        }

        return $uncategorizedCategory;
    }

    public function getChartFromTransaction(Company $company, $transaction, string $transactionType): Account
    {
        if ($transactionType === 'income') {
            $chart = $company->accounts()->where('type', AccountType::UncategorizedRevenue)->where('name', 'Uncategorized Income')->first();
        } else {
            $chart = $company->accounts()->where('type', AccountType::UncategorizedExpense)->where('name', 'Uncategorized Expense')->first();
        }

        return $chart;
    }

    public function processNewBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount, $accessToken): BankAccount
    {
        $bankAccount = $connectedBankAccount->bankAccount()->create([
            'company_id' => $company->id,
            'institution_id' => $connectedBankAccount->institution_id,
            'type' => $connectedBankAccount->type,
            'number' => $connectedBankAccount->mask,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
        ]);

        $this->mapAccountDetails($bankAccount, $company, $accessToken, $connectedBankAccount);

        return $bankAccount;
    }

    public function mapAccountDetails(BankAccount $bankAccount, Company $company, $accessToken, ConnectedBankAccount $connectedBankAccount): void
    {
        $this->ensureCurrencyExists($company->id, 'USD');

        $accountSubtype = $this->getAccountSubtype($bankAccount->type->value);

        $accountSubtypeId = $this->resolveAccountSubtypeId($company, $accountSubtype);

        $bankAccount->account()->create([
            'company_id' => $company->id,
            'name' => $connectedBankAccount->name,
            'currency_code' => 'USD',
            'description' => $connectedBankAccount->name,
            'subtype_id' => $accountSubtypeId,
            'active' => true,
        ]);
    }

    public function ensureCurrencyExists(int $companyId, string $currencyCode): void
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $hasDefaultCurrency = $defaultCurrency !== null;

        $currency_code = currency($currencyCode);

        Currency::firstOrCreate([
            'company_id' => $companyId,
            'code' => $currencyCode,
        ], [
            'name' => $currency_code->getName(),
            'rate' => $currency_code->getRate(),
            'precision' => $currency_code->getPrecision(),
            'symbol' => $currency_code->getSymbol(),
            'symbol_first' => $currency_code->isSymbolFirst(),
            'decimal_mark' => $currency_code->getDecimalMark(),
            'thousands_separator' => $currency_code->getThousandsSeparator(),
            'enabled' => ! $hasDefaultCurrency,
        ]);
    }

    public function getAccountSubtype(string $plaidType): string
    {
        return match ($plaidType) {
            'depository' => 'Cash and Cash Equivalents',
            'credit' => 'Short-Term Borrowings',
            'loan' => 'Long-Term Borrowings',
            'investment' => 'Long-Term Investments',
            'other' => 'Other Current Assets',
        };
    }

    public function resolveAccountSubtypeId(Company $company, string $accountSubtype): int
    {
        return AccountSubtype::where('company_id', $company->id)
            ->where('name', $accountSubtype)
            ->value('id');
    }
}
