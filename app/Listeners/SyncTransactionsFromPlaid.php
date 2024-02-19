<?php

namespace App\Listeners;

use App\Enums\Accounting\AccountType;
use App\Events\PlaidSuccess;
use App\Models\Accounting\Account;
use App\Models\Company;
use App\Models\Setting\Category;
use App\Services\PlaidService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SyncTransactionsFromPlaid
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
    public function handle(PlaidSuccess $event): void
    {
        $accessToken = $event->accessToken;
        $company = $event->company;

        $syncResponse = $this->plaid->syncTransactions($accessToken);

        foreach ($syncResponse->added as $transaction) {
            $this->storeTransaction($company, $transaction);
        }
    }

    public function storeTransaction(Company $company, $transaction): void
    {
        $account = $company->accounts()->where('external_account_id', $transaction->account_id)->first();

        if ($account === null) {
            return;
        }

        $transactionType = $transaction->amount < 0 ? 'income' : 'expense';
        $method = $transactionType === 'income' ? 'deposit' : 'withdrawal';
        $paymentChannel = $transaction->payment_channel ?? 'other';
        $category = $this->getCategoryFromTransaction($company, $transaction, $transactionType);
        $chart = $category->account ?? $this->getChartFromTransaction($company, $transaction, $transactionType);

        // Use datetime and if null, then use date and convert to datetime
        $postedAt = $transaction->datetime ?? Carbon::parse($transaction->date)->toDateTimeString();

        $description = $transaction->original_description ?? $transaction->name;
        $cleanDescription = preg_replace('/\\*\\/\\/$/', '', $description);
        $cleanDescription = trim(preg_replace('/\\s+/', ' ', $cleanDescription));

        $account->transactions()->create([
            'company_id' => $company->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'chart_id' => $chart->id,
            'amount' => abs($transaction->amount),
            'type' => $transactionType,
            'method' => $method,
            'payment_channel' => $paymentChannel,
            'posted_at' => $postedAt,
            'description' => $cleanDescription,
            'pending' => $transaction->pending,
            'reviewed' => false,
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
            $chart = $company->accounts()->where('type', AccountType::OperatingRevenue)->where('name', 'Uncategorized Income')->first();
        } else {
            $chart = $company->accounts()->where('type', AccountType::OperatingExpense)->where('name', 'Uncategorized Expense')->first();
        }

        return $chart;
    }
}
