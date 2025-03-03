<?php

namespace App\Services;

use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Repositories\Accounting\JournalEntryRepository;
use App\Utilities\Currency\CurrencyAccessor;
use App\ValueObjects\Money;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(
        protected JournalEntryRepository $journalEntryRepository
    ) {}

    public function getDebitBalance(Account $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        return new Money($query->total_debit, $account->currency_code);
    }

    public function getCreditBalance(Account $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        return new Money($query->total_credit, $account->currency_code);
    }

    public function getNetMovement(Account $account, string $startDate, string $endDate): Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        return new Money($netMovement, $account->currency_code);
    }

    public function getStartingBalance(Account $account, string $startDate, bool $override = false): ?Money
    {
        if ($override === false && $account->category->isNominal()) {
            return null;
        }

        $query = $this->getAccountBalances($startDate, $startDate, [$account->id])->first();

        return new Money($query->starting_balance ?? 0, $account->currency_code);
    }

    public function getEndingBalance(Account $account, string $startDate, string $endDate): ?Money
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        if ($account->category->isNominal()) {
            return new Money($netMovement, $account->currency_code);
        }

        $endingBalance = ($query->starting_balance ?? 0) + $netMovement;

        return new Money($endingBalance, $account->currency_code);
    }

    private function calculateNetMovementByCategory(AccountCategory $category, int $debitBalance, int $creditBalance): int
    {
        if ($category->isNormalDebitBalance()) {
            return $debitBalance - $creditBalance;
        } else {
            return $creditBalance - $debitBalance;
        }
    }

    public function getBalances(Account $account, string $startDate, string $endDate): array
    {
        $query = $this->getAccountBalances($startDate, $endDate, [$account->id])->first();

        $needStartingBalances = $account->category->isReal();

        $netMovement = $this->calculateNetMovementByCategory(
            $account->category,
            $query->total_debit ?? 0,
            $query->total_credit ?? 0
        );

        $balances = [
            'debit_balance' => $query->total_debit,
            'credit_balance' => $query->total_credit,
            'net_movement' => $netMovement,
            'starting_balance' => $needStartingBalances ? ($query->starting_balance ?? 0) : null,
            'ending_balance' => $needStartingBalances
                ? ($query->starting_balance ?? 0) + $netMovement
                : $netMovement, // For nominal accounts, ending balance is just the net movement
        ];

        // Return balances, filtering out any null values
        return array_filter($balances, static fn ($value) => $value !== null);
    }

    public function getTransactionDetailsSubquery(string $startDate, string $endDate, ?string $entityId = null): Closure
    {
        return static function ($query) use ($startDate, $endDate, $entityId) {
            $query->select(
                'journal_entries.id',
                'journal_entries.account_id',
                'journal_entries.transaction_id',
                'journal_entries.type',
                'journal_entries.amount',
                'journal_entries.description',
                DB::raw('journal_entries.amount * IF(journal_entries.type = "debit", 1, -1) AS signed_amount')
            )
                ->whereBetween('transactions.posted_at', [$startDate, $endDate])
                ->join('transactions', 'transactions.id', '=', 'journal_entries.transaction_id')
                ->orderBy('transactions.posted_at')
                ->with('transaction:id,type,description,posted_at,is_payment,transactionable_id,transactionable_type');

            if ($entityId) {
                $entityId = (int) $entityId;
                if ($entityId < 0) {
                    $query->whereHas('transaction', function ($query) use ($entityId) {
                        $query->whereHasMorph('transactionable', [Bill::class], function ($query) use ($entityId) {
                            $query->where('vendor_id', abs($entityId));
                        });
                    });
                } else {
                    $query->whereHas('transaction', function ($query) use ($entityId) {
                        $query->whereHasMorph('transactionable', [Invoice::class], function ($query) use ($entityId) {
                            $query->where('client_id', $entityId);
                        });
                    });
                }
            }
        };
    }

    public function getAccountBalances(string $startDate, string $endDate, array $accountIds = []): Builder
    {
        $accountIds = array_map('intval', $accountIds);

        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.category',
                'accounts.type',
                'accounts.subtype_id',
                'accounts.currency_code',
                'accounts.code',
            ])
            ->addSelect([
                DB::raw("
                    COALESCE(
                        IF(accounts.category IN ('asset', 'expense'),
                            SUM(IF(journal_entries.type = 'debit' AND transactions.posted_at < ?, journal_entries.amount, 0)) -
                            SUM(IF(journal_entries.type = 'credit' AND transactions.posted_at < ?, journal_entries.amount, 0)),
                            SUM(IF(journal_entries.type = 'credit' AND transactions.posted_at < ?, journal_entries.amount, 0)) -
                            SUM(IF(journal_entries.type = 'debit' AND transactions.posted_at < ?, journal_entries.amount, 0))
                        ), 0
                    ) AS starting_balance
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries.type = 'debit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries.amount, 0)
                    ), 0) AS total_debit
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries.type = 'credit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries.amount, 0)
                    ), 0) AS total_credit
                "),
            ])
            ->join('journal_entries', 'journal_entries.account_id', '=', 'accounts.id')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            ->groupBy([
                'accounts.id',
                'accounts.name',
                'accounts.category',
                'accounts.type',
                'accounts.subtype_id',
                'accounts.currency_code',
                'accounts.code',
            ])
            ->with(['subtype:id,name,inverse_cash_flow']);

        if (! empty($accountIds)) {
            $query->whereIn('accounts.id', $accountIds);
        }

        $query->addBinding([$startDate, $startDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate], 'select');

        return $query;
    }

    public function getCashFlowAccountBalances(string $startDate, string $endDate, array $accountIds = []): Builder
    {
        $accountIds = array_map('intval', $accountIds);

        $query = Account::query()
            ->select([
                'accounts.id',
                'accounts.name',
                'accounts.category',
                'accounts.type',
                'accounts.subtype_id',
                'accounts.currency_code',
                'accounts.code',
            ])
            ->addSelect([
                DB::raw("
                    COALESCE(
                        IF(accounts.category IN ('asset', 'expense'),
                            SUM(IF(journal_entries.type = 'debit' AND transactions.posted_at < ?, journal_entries.amount, 0)) -
                            SUM(IF(journal_entries.type = 'credit' AND transactions.posted_at < ?, journal_entries.amount, 0)),
                            SUM(IF(journal_entries.type = 'credit' AND transactions.posted_at < ?, journal_entries.amount, 0)) -
                            SUM(IF(journal_entries.type = 'debit' AND transactions.posted_at < ?, journal_entries.amount, 0))
                        ), 0
                    ) AS starting_balance
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries.type = 'debit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries.amount, 0)
                    ), 0) AS total_debit
                "),
                DB::raw("
                    COALESCE(SUM(
                        IF(journal_entries.type = 'credit' AND transactions.posted_at BETWEEN ? AND ?, journal_entries.amount, 0)
                    ), 0) AS total_credit
                "),
            ])
            ->join('journal_entries', 'journal_entries.account_id', '=', 'accounts.id')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            ->whereExists(function (\Illuminate\Database\Query\Builder $subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('journal_entries as je')
                    ->join('bank_accounts', 'bank_accounts.account_id', '=', 'je.account_id') // Join bank_accounts on account_id
                    ->whereNotNull('bank_accounts.id') // Ensure there is a linked BankAccount
                    ->whereColumn('je.transaction_id', 'journal_entries.transaction_id');
            })
            ->groupBy([
                'accounts.id',
                'accounts.name',
                'accounts.category',
                'accounts.type',
                'accounts.subtype_id',
                'accounts.currency_code',
                'accounts.code',
            ])
            ->with(['subtype:id,name,inverse_cash_flow']);

        if (! empty($accountIds)) {
            $query->whereIn('accounts.id', $accountIds);
        }

        $query->addBinding([$startDate, $startDate, $startDate, $startDate, $startDate, $endDate, $startDate, $endDate], 'select');

        return $query;
    }

    public function getTotalBalanceForAllBankAccounts(string $startDate, string $endDate): Money
    {
        $accountIds = Account::whereHas('bankAccount')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return new Money(0, CurrencyAccessor::getDefaultCurrency());
        }

        $result = DB::table('journal_entries')
            ->join('transactions', function (JoinClause $join) use ($endDate) {
                $join->on('transactions.id', '=', 'journal_entries.transaction_id')
                    ->where('transactions.posted_at', '<=', $endDate);
            })
            ->whereIn('journal_entries.account_id', $accountIds)
            ->selectRaw('
            SUM(CASE
                WHEN transactions.posted_at < ? AND journal_entries.type = "debit" THEN journal_entries.amount
                WHEN transactions.posted_at < ? AND journal_entries.type = "credit" THEN -journal_entries.amount
                ELSE 0
            END) AS totalStartingBalance,
            SUM(CASE
                WHEN transactions.posted_at BETWEEN ? AND ? AND journal_entries.type = "debit" THEN journal_entries.amount
                WHEN transactions.posted_at BETWEEN ? AND ? AND journal_entries.type = "credit" THEN -journal_entries.amount
                ELSE 0
            END) AS totalNetMovement
        ', [
                $startDate,
                $startDate,
                $startDate,
                $endDate,
                $startDate,
                $endDate,
            ])
            ->first();

        $totalBalance = $result->totalStartingBalance + $result->totalNetMovement;

        return new Money($totalBalance, CurrencyAccessor::getDefaultCurrency());
    }

    public function getStartingBalanceForAllBankAccounts(string $startDate): Money
    {
        $accountIds = Account::whereHas('bankAccount')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return new Money(0, CurrencyAccessor::getDefaultCurrency());
        }

        $result = DB::table('journal_entries')
            ->join('transactions', function (JoinClause $join) use ($startDate) {
                $join->on('transactions.id', '=', 'journal_entries.transaction_id')
                    ->where('transactions.posted_at', '<', $startDate);
            })
            ->whereIn('journal_entries.account_id', $accountIds)
            ->selectRaw('
            SUM(CASE
                WHEN transactions.posted_at < ? AND journal_entries.type = "debit" THEN journal_entries.amount
                WHEN transactions.posted_at < ? AND journal_entries.type = "credit" THEN -journal_entries.amount
                ELSE 0
            END) AS totalStartingBalance
        ', [
                $startDate,
                $startDate,
            ])
            ->first();

        return new Money($result->totalStartingBalance ?? 0, CurrencyAccessor::getDefaultCurrency());
    }

    public function getBankAccountBalances(string $startDate, string $endDate): Builder | array
    {
        $accountIds = Account::whereHas('bankAccount')
            ->pluck('id')
            ->toArray();

        if (empty($accountIds)) {
            return [];
        }

        return $this->getAccountBalances($startDate, $endDate, $accountIds);
    }

    public function getEarliestTransactionDate(): string
    {
        $earliestDate = Transaction::min('posted_at');

        return $earliestDate ?? today()->toDateTimeString();
    }

    public function getUnpaidClientInvoices(?string $asOfDate = null): Builder
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        return Invoice::query()
            ->select([
                'invoices.id',
                'invoices.client_id',
                'invoices.due_date',
                'invoices.amount_due',
                'invoices.currency_code',
                DB::raw('DATEDIFF(?, invoices.due_date) as days_overdue'),
            ])
            ->addBinding([$asOfDate], 'select')
            ->unpaid()
            ->where('amount_due', '>', 0);
    }

    public function getUnpaidVendorBills(?string $asOfDate = null): Builder
    {
        $asOfDate = $asOfDate ?? now()->toDateString();

        return Bill::query()
            ->select([
                'bills.id',
                'bills.vendor_id',
                'bills.due_date',
                'bills.amount_due',
                'bills.currency_code',
                DB::raw('DATEDIFF(?, bills.due_date) as days_overdue'),
            ])
            ->addBinding([$asOfDate], 'select')
            ->unpaid()
            ->where('amount_due', '>', 0);
    }
}
