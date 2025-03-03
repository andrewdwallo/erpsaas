<?php

namespace App\Services;

use App\Collections\Accounting\DocumentCollection;
use App\Contracts\BalanceFormattable;
use App\DTO\AccountBalanceDTO;
use App\DTO\AccountCategoryDTO;
use App\DTO\AccountDTO;
use App\DTO\AccountTransactionDTO;
use App\DTO\AccountTypeDTO;
use App\DTO\AgingBucketDTO;
use App\DTO\CashFlowOverviewDTO;
use App\DTO\EntityBalanceDTO;
use App\DTO\EntityReportDTO;
use App\DTO\PaymentMetricsDTO;
use App\DTO\ReportDTO;
use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\BillStatus;
use App\Enums\Accounting\DocumentEntityType;
use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Support\Column;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class ReportService
{
    public function __construct(
        protected AccountService $accountService,
    ) {}

    /**
     * @param  class-string<BalanceFormattable>|null  $dtoClass
     */
    public function formatBalances(array $balances, ?string $dtoClass = null, bool $formatZeros = true): BalanceFormattable | array
    {
        $dtoClass ??= AccountBalanceDTO::class;

        $formattedBalances = array_map(static function ($balance) use ($formatZeros) {
            if (! $formatZeros && $balance === 0) {
                return '';
            }

            return CurrencyConverter::formatCentsToMoney($balance);
        }, $balances);

        if (! $dtoClass) {
            return $formattedBalances;
        }

        return $dtoClass::fromArray($formattedBalances);
    }

    public function buildAccountBalanceReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        $orderedCategories = AccountCategory::getOrderedCategories();

        $accounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $columnNameKeys = array_map(fn (Column $column) => $column->getName(), $columns);

        $accountCategories = [];
        $reportTotalBalances = [];

        foreach ($orderedCategories as $category) {
            $accountsInCategory = $accounts->where('category', $category);

            $relevantFields = array_intersect($category->getRelevantBalanceFields(), $columnNameKeys);

            $categorySummaryBalances = array_fill_keys($relevantFields, 0);

            $categoryAccounts = [];

            /** @var Account $account */
            foreach ($accountsInCategory as $account) {
                $accountBalances = $this->calculateAccountBalances($account);

                foreach ($relevantFields as $field) {
                    $categorySummaryBalances[$field] += $accountBalances[$field];
                }

                $formattedAccountBalances = $this->formatBalances($accountBalances);

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->code,
                    $account->id,
                    $formattedAccountBalances,
                    Carbon::parse($startDate)->toDateString(),
                    Carbon::parse($endDate)->toDateString(),
                );
            }

            foreach ($relevantFields as $field) {
                $reportTotalBalances[$field] = ($reportTotalBalances[$field] ?? 0) + $categorySummaryBalances[$field];
            }

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances);

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
        );
    }

    public function calculateAccountBalances(Account $account): array
    {
        $category = $account->category;
        $balances = [
            'debit_balance' => $account->total_debit ?? 0,
            'credit_balance' => $account->total_credit ?? 0,
        ];

        if ($category->isNormalDebitBalance()) {
            $balances['net_movement'] = $balances['debit_balance'] - $balances['credit_balance'];
        } else {
            $balances['net_movement'] = $balances['credit_balance'] - $balances['debit_balance'];
        }

        if ($category->isReal()) {
            $balances['starting_balance'] = $account->starting_balance ?? 0;
            $balances['ending_balance'] = $balances['starting_balance'] + $balances['net_movement'];
        }

        return $balances;
    }

    public function calculateRetainedEarnings(?string $startDate, string $endDate): Money
    {
        $startDate ??= Carbon::parse($this->accountService->getEarliestTransactionDate())->toDateTimeString();
        $revenueAccounts = $this->accountService->getAccountBalances($startDate, $endDate)->where('category', AccountCategory::Revenue)->get();

        $expenseAccounts = $this->accountService->getAccountBalances($startDate, $endDate)->where('category', AccountCategory::Expense)->get();

        $revenueTotal = 0;
        $expenseTotal = 0;

        foreach ($revenueAccounts as $account) {
            $revenueBalances = $this->calculateAccountBalances($account);
            $revenueTotal += $revenueBalances['net_movement'];
        }

        foreach ($expenseAccounts as $account) {
            $expenseBalances = $this->calculateAccountBalances($account);
            $expenseTotal += $expenseBalances['net_movement'];
        }

        $retainedEarnings = $revenueTotal - $expenseTotal;

        return new Money($retainedEarnings, CurrencyAccessor::getDefaultCurrency());
    }

    public function buildAccountTransactionsReport(string $startDate, string $endDate, ?array $columns = null, ?string $accountId = 'all', ?string $entityId = 'all'): ReportDTO
    {
        $columns ??= [];
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $accountIds = $accountId !== 'all' ? [$accountId] : [];

        $entityId = $entityId !== 'all' ? $entityId : null;

        $query = $this->accountService->getAccountBalances($startDate, $endDate, $accountIds)
            ->orderByRaw('LENGTH(code), code');

        $accounts = $query->with(['journalEntries' => $this->accountService->getTransactionDetailsSubquery($startDate, $endDate, $entityId)])->get();

        $reportCategories = [];

        foreach ($accounts as $account) {
            /** @var Account $account */
            if ($account->journalEntries->isEmpty()) {
                continue;
            }

            $accountTransactions = [];
            $currentBalance = $account->starting_balance;
            $periodDebitTotal = 0;
            $periodCreditTotal = 0;

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Starting Balance',
                description: '',
                debit: '',
                credit: '',
                balance: money($currentBalance, $defaultCurrency)->format(),
                type: null,
                tableAction: null
            );

            foreach ($account->journalEntries as $journalEntry) {
                $transaction = $journalEntry->transaction;
                $signedAmount = $journalEntry->signed_amount;
                $amount = $journalEntry->getRawOriginal('amount');

                if ($journalEntry->type->isDebit()) {
                    $periodDebitTotal += $amount;
                } else {
                    $periodCreditTotal += $amount;
                }

                if ($account->category->isNormalDebitBalance()) {
                    $currentBalance += $signedAmount;
                } else {
                    $currentBalance -= $signedAmount;
                }

                $formattedAmount = money(abs($signedAmount), $defaultCurrency)->format();

                $accountTransactions[] = new AccountTransactionDTO(
                    id: $transaction->id,
                    date: $transaction->posted_at->toDefaultDateFormat(),
                    description: $journalEntry->description ?: $transaction->description ?? 'Add a description',
                    debit: $journalEntry->type->isDebit() ? $formattedAmount : '',
                    credit: $journalEntry->type->isCredit() ? $formattedAmount : '',
                    balance: money($currentBalance, $defaultCurrency)->format(),
                    type: $transaction->type,
                    tableAction: $this->determineTableAction($transaction),
                );
            }

            $balanceChange = $currentBalance - $account->starting_balance;

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Totals and Ending Balance',
                description: '',
                debit: money($periodDebitTotal, $defaultCurrency)->format(),
                credit: money($periodCreditTotal, $defaultCurrency)->format(),
                balance: money($currentBalance, $defaultCurrency)->format(),
                type: null,
                tableAction: null
            );

            $accountTransactions[] = new AccountTransactionDTO(
                id: null,
                date: 'Balance Change',
                description: '',
                debit: '',
                credit: '',
                balance: money($balanceChange, $defaultCurrency)->format(),
                type: null,
                tableAction: null
            );

            $reportCategories[] = [
                'category' => $account->name,
                'under' => $account->category->getLabel() . ' > ' . $account->subtype->name,
                'transactions' => $accountTransactions,
            ];
        }

        return new ReportDTO(categories: $reportCategories, fields: $columns);
    }

    private function determineTableAction(Transaction $transaction): array
    {
        if ($transaction->transactionable_type === null || $transaction->is_payment) {
            return [
                'type' => 'transaction',
                'action' => match ($transaction->type) {
                    TransactionType::Journal => 'editJournalTransaction',
                    TransactionType::Transfer => 'editTransfer',
                    default => 'editTransaction',
                },
                'id' => $transaction->id,
            ];
        }

        return [
            'type' => 'transactionable',
            'model' => $transaction->transactionable_type,
            'id' => $transaction->transactionable_id,
        ];
    }

    public function buildTrialBalanceReport(string $trialBalanceType, string $asOfDate, array $columns = []): ReportDTO
    {
        $asOfDateCarbon = Carbon::parse($asOfDate);
        $startDateCarbon = Carbon::parse($this->accountService->getEarliestTransactionDate());

        $orderedCategories = AccountCategory::getOrderedCategories();

        $isPostClosingTrialBalance = $trialBalanceType === 'postClosing';

        $accounts = $this->accountService->getAccountBalances($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())
            ->when($isPostClosingTrialBalance, fn (Builder $query) => $query->whereNotIn('category', [AccountCategory::Revenue, AccountCategory::Expense]))
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $balanceFields = ['debit_balance', 'credit_balance'];

        $accountCategories = [];
        $reportTotalBalances = array_fill_keys($balanceFields, 0);

        foreach ($orderedCategories as $category) {
            $accountsInCategory = $accounts->where('category', $category);

            $categorySummaryBalances = array_fill_keys($balanceFields, 0);

            $categoryAccounts = [];

            /** @var Account $account */
            foreach ($accountsInCategory as $account) {
                $accountBalances = $this->calculateAccountBalances($account);

                $endingBalance = $accountBalances['ending_balance'] ?? $accountBalances['net_movement'];

                $trialBalance = $this->calculateTrialBalances($account->category, $endingBalance);

                foreach ($trialBalance as $balanceType => $balance) {
                    $categorySummaryBalances[$balanceType] += $balance;
                }

                $formattedAccountBalances = $this->formatBalances($trialBalance);

                $categoryAccounts[] = new AccountDTO(
                    $account->name,
                    $account->code,
                    $account->id,
                    $formattedAccountBalances,
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );
            }

            if ($category === AccountCategory::Equity && $isPostClosingTrialBalance) {
                $retainedEarningsAmount = $this->calculateRetainedEarnings($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())->getAmount();
                $isCredit = $retainedEarningsAmount >= 0;

                $categorySummaryBalances[$isCredit ? 'credit_balance' : 'debit_balance'] += abs($retainedEarningsAmount);

                $categoryAccounts[] = new AccountDTO(
                    'Retained Earnings',
                    'RE',
                    null,
                    $this->formatBalances([
                        'debit_balance' => $isCredit ? 0 : abs($retainedEarningsAmount),
                        'credit_balance' => $isCredit ? $retainedEarningsAmount : 0,
                    ]),
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );
            }

            foreach ($categorySummaryBalances as $balanceType => $balance) {
                $reportTotalBalances[$balanceType] += $balance;
            }

            $formattedCategorySummaryBalances = $this->formatBalances($categorySummaryBalances);

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $formattedCategorySummaryBalances,
            );
        }

        $formattedReportTotalBalances = $this->formatBalances($reportTotalBalances);

        return new ReportDTO(categories: $accountCategories, overallTotal: $formattedReportTotalBalances, fields: $columns, reportType: $trialBalanceType);
    }

    public function getRetainedEarningsBalances(string $startDate, string $endDate): BalanceFormattable | array
    {
        $retainedEarningsAmount = $this->calculateRetainedEarnings($startDate, $endDate)->getAmount();

        $isCredit = $retainedEarningsAmount >= 0;
        $retainedEarningsDebitAmount = $isCredit ? 0 : abs($retainedEarningsAmount);
        $retainedEarningsCreditAmount = $isCredit ? $retainedEarningsAmount : 0;

        return $this->formatBalances([
            'debit_balance' => $retainedEarningsDebitAmount,
            'credit_balance' => $retainedEarningsCreditAmount,
        ]);
    }

    public function calculateTrialBalances(AccountCategory $category, int $endingBalance): array
    {
        if ($category->isNormalDebitBalance()) {
            if ($endingBalance >= 0) {
                return ['debit_balance' => $endingBalance, 'credit_balance' => 0];
            }

            return ['debit_balance' => 0, 'credit_balance' => abs($endingBalance)];
        }

        if ($endingBalance >= 0) {
            return ['debit_balance' => 0, 'credit_balance' => $endingBalance];
        }

        return ['debit_balance' => abs($endingBalance), 'credit_balance' => 0];
    }

    public function buildIncomeStatementReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        // Query only relevant accounts and sort them at the query level
        $revenueAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->where('category', AccountCategory::Revenue)
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $cogsAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->whereRelation('subtype', 'name', 'Cost of Goods Sold')
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $expenseAccounts = $this->accountService->getAccountBalances($startDate, $endDate)
            ->where('category', AccountCategory::Expense)
            ->whereRelation('subtype', 'name', '!=', 'Cost of Goods Sold')
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $accountCategories = [];
        $totalRevenue = 0;
        $totalCogs = 0;
        $totalExpenses = 0;

        // Define category groups
        $categoryGroups = [
            AccountCategory::Revenue->getPluralLabel() => [
                'accounts' => $revenueAccounts,
                'total' => &$totalRevenue,
            ],
            'Cost of Goods Sold' => [
                'accounts' => $cogsAccounts,
                'total' => &$totalCogs,
            ],
            AccountCategory::Expense->getPluralLabel() => [
                'accounts' => $expenseAccounts,
                'total' => &$totalExpenses,
            ],
        ];

        // Process each category group
        foreach ($categoryGroups as $label => $group) {
            $categoryAccounts = [];
            $netMovement = 0;

            foreach ($group['accounts'] as $account) {
                // Use the category type based on label
                $category = match ($label) {
                    AccountCategory::Revenue->getPluralLabel() => AccountCategory::Revenue,
                    AccountCategory::Expense->getPluralLabel(), 'Cost of Goods Sold' => AccountCategory::Expense,
                    default => null
                };

                if ($category !== null) {
                    $accountBalances = $this->calculateAccountBalances($account);
                    $movement = $accountBalances['net_movement'];
                    $netMovement += $movement;
                    $group['total'] += $movement;

                    $categoryAccounts[] = new AccountDTO(
                        $account->name,
                        $account->code,
                        $account->id,
                        $this->formatBalances(['net_movement' => $movement]),
                        Carbon::parse($startDate)->toDateString(),
                        Carbon::parse($endDate)->toDateString(),
                    );
                }
            }

            $accountCategories[$label] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                summary: $this->formatBalances(['net_movement' => $netMovement])
            );
        }

        // Calculate gross and net profit
        $grossProfit = $totalRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;
        $formattedReportTotalBalances = $this->formatBalances(['net_movement' => $netProfit]);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }

    public function buildCashFlowStatementReport(string $startDate, string $endDate, array $columns = []): ReportDTO
    {
        $sections = [
            'Operating Activities' => $this->buildOperatingActivities($startDate, $endDate),
            'Investing Activities' => $this->buildInvestingActivities($startDate, $endDate),
            'Financing Activities' => $this->buildFinancingActivities($startDate, $endDate),
        ];

        $totalCashFlows = $this->calculateTotalCashFlows($sections, $startDate);

        $overview = $this->buildCashFlowOverview($startDate, $endDate);

        return new ReportDTO(
            categories: $sections,
            overallTotal: $totalCashFlows,
            fields: $columns,
            overview: $overview,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }

    private function calculateTotalCashFlows(array $sections, string $startDate): BalanceFormattable | array
    {
        $totalInflow = 0;
        $totalOutflow = 0;
        $startingBalance = $this->accountService->getStartingBalanceForAllBankAccounts($startDate)->getAmount();

        foreach ($sections as $section) {
            $netMovement = $section->summary->netMovement ?? 0;

            $numericNetMovement = CurrencyConverter::convertToCents($netMovement);

            if ($numericNetMovement > 0) {
                $totalInflow += $numericNetMovement;
            } else {
                $totalOutflow += $numericNetMovement;
            }
        }

        $netCashChange = $totalInflow + $totalOutflow;
        $endingBalance = $startingBalance + $netCashChange;

        return $this->formatBalances([
            'starting_balance' => $startingBalance,
            'debit_balance' => $totalInflow,
            'credit_balance' => abs($totalOutflow),
            'net_movement' => $netCashChange,
            'ending_balance' => $endingBalance,
        ]);
    }

    private function buildCashFlowOverview(string $startDate, string $endDate): CashFlowOverviewDTO
    {
        $accounts = $this->accountService->getBankAccountBalances($startDate, $endDate)->get();

        $startingBalanceAccounts = [];
        $endingBalanceAccounts = [];

        $startingBalanceTotal = 0;
        $endingBalanceTotal = 0;

        foreach ($accounts as $account) {
            $accountBalances = $this->calculateAccountBalances($account);

            $startingBalanceTotal += $accountBalances['starting_balance'];
            $endingBalanceTotal += $accountBalances['ending_balance'];

            $startingBalanceAccounts[] = new AccountDTO(
                accountName: $account->name,
                accountCode: $account->code,
                accountId: $account->id,
                balance: $this->formatBalances(['starting_balance' => $accountBalances['starting_balance']]),
                startDate: $startDate,
                endDate: $endDate,
            );

            $endingBalanceAccounts[] = new AccountDTO(
                accountName: $account->name,
                accountCode: $account->code,
                accountId: $account->id,
                balance: $this->formatBalances(['ending_balance' => $accountBalances['ending_balance']]),
                startDate: $startDate,
                endDate: $endDate,
            );
        }

        $startingBalanceSummary = $this->formatBalances(['starting_balance' => $startingBalanceTotal]);
        $endingBalanceSummary = $this->formatBalances(['ending_balance' => $endingBalanceTotal]);

        $overviewCategories = [
            'Starting Balance' => new AccountCategoryDTO(
                accounts: $startingBalanceAccounts,
                summary: $startingBalanceSummary,
            ),
            'Ending Balance' => new AccountCategoryDTO(
                accounts: $endingBalanceAccounts,
                summary: $endingBalanceSummary,
            ),
        ];

        return new CashFlowOverviewDTO($overviewCategories);
    }

    private function buildOperatingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('accounts.type', [
                AccountType::OperatingRevenue,
                AccountType::UncategorizedRevenue,
                AccountType::ContraRevenue,
                AccountType::OperatingExpense,
                AccountType::NonOperatingExpense,
                AccountType::UncategorizedExpense,
                AccountType::ContraExpense,
                AccountType::CurrentAsset,
            ])
            ->whereRelation('subtype', 'name', '!=', 'Cash and Cash Equivalents')
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $adjustments = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('accounts.type', [
                AccountType::ContraAsset,
                AccountType::CurrentLiability,
            ])
            ->whereRelation('subtype', 'name', '!=', 'Short-Term Borrowings')
            ->orderByRaw('LENGTH(code), code')
            ->get();

        return $this->formatSectionAccounts($accounts, $adjustments, $startDate, $endDate);
    }

    private function buildInvestingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('accounts.type', [AccountType::NonCurrentAsset])
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $adjustments = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->whereIn('accounts.type', [AccountType::NonOperatingRevenue])
            ->orderByRaw('LENGTH(code), code')
            ->get();

        return $this->formatSectionAccounts($accounts, $adjustments, $startDate, $endDate);
    }

    private function buildFinancingActivities(string $startDate, string $endDate): AccountCategoryDTO
    {
        $accounts = $this->accountService->getCashFlowAccountBalances($startDate, $endDate)
            ->where(function (Builder $query) {
                $query->whereIn('accounts.type', [
                    AccountType::Equity,
                    AccountType::NonCurrentLiability,
                ])
                    ->orWhere(function (Builder $subQuery) {
                        $subQuery->where('accounts.type', AccountType::CurrentLiability)
                            ->whereRelation('subtype', 'name', 'Short-Term Borrowings');
                    });
            })
            ->orderByRaw('LENGTH(code), code')
            ->get();

        return $this->formatSectionAccounts($accounts, [], $startDate, $endDate);
    }

    private function formatSectionAccounts($accounts, $adjustments, string $startDate, string $endDate): AccountCategoryDTO
    {
        $categoryAccountsByType = [];
        $sectionTotal = 0;
        $subCategoryTotals = [];

        // Process accounts and adjustments
        /** @var Account[] $entries */
        foreach ([$accounts, $adjustments] as $entries) {
            foreach ($entries as $entry) {
                $accountCategory = $entry->type->getCategory();
                $accountBalances = $this->calculateAccountBalances($entry);
                $netCashFlow = $accountBalances['net_movement'] ?? 0;

                if ($entry->subtype->inverse_cash_flow) {
                    $netCashFlow *= -1;
                }

                // Accumulate totals
                $sectionTotal += $netCashFlow;
                $accountTypeName = $entry->subtype->name;
                $subCategoryTotals[$accountTypeName] = ($subCategoryTotals[$accountTypeName] ?? 0) + $netCashFlow;

                // Create AccountDTO and group by account type
                $accountDTO = new AccountDTO(
                    $entry->name,
                    $entry->code,
                    $entry->id,
                    $this->formatBalances(['net_movement' => $netCashFlow]),
                    $startDate,
                    $endDate
                );

                $categoryAccountsByType[$accountTypeName][] = $accountDTO;
            }
        }

        // Prepare AccountTypeDTO for each account type with the accumulated totals
        $subCategories = [];
        foreach ($categoryAccountsByType as $typeName => $accountsInType) {
            $typeTotal = $subCategoryTotals[$typeName] ?? 0;
            $formattedTypeTotal = $this->formatBalances(['net_movement' => $typeTotal]);
            $subCategories[$typeName] = new AccountTypeDTO(
                accounts: $accountsInType,
                summary: $formattedTypeTotal
            );
        }

        // Format the overall section total as the section summary
        $formattedSectionTotal = $this->formatBalances(['net_movement' => $sectionTotal]);

        return new AccountCategoryDTO(
            accounts: [], // No direct accounts at the section level
            types: $subCategories, // Grouped by AccountTypeDTO
            summary: $formattedSectionTotal,
        );
    }

    public function buildBalanceSheetReport(string $asOfDate, array $columns = []): ReportDTO
    {
        $asOfDateCarbon = Carbon::parse($asOfDate);
        $startDateCarbon = Carbon::parse($this->accountService->getEarliestTransactionDate());

        $orderedCategories = array_filter(AccountCategory::getOrderedCategories(), fn (AccountCategory $category) => $category->isReal());

        $accounts = $this->accountService->getAccountBalances($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())
            ->whereIn('category', $orderedCategories)
            ->orderByRaw('LENGTH(code), code')
            ->get();

        $accountCategories = [];
        $reportTotalBalances = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
        ];

        foreach ($orderedCategories as $category) {
            $categorySummaryBalances = ['ending_balance' => 0];

            $categoryAccountsByType = [];
            $categoryAccounts = [];
            $subCategoryTotals = [];

            /** @var Account $account */
            foreach ($accounts as $account) {
                if ($account->type->getCategory() === $category) {
                    $accountBalances = $this->calculateAccountBalances($account);
                    $endingBalance = $accountBalances['ending_balance'] ?? $accountBalances['net_movement'];

                    $categorySummaryBalances['ending_balance'] += $endingBalance;

                    $formattedAccountBalances = $this->formatBalances($accountBalances);

                    $accountDTO = new AccountDTO(
                        $account->name,
                        $account->code,
                        $account->id,
                        $formattedAccountBalances,
                        startDate: $startDateCarbon->toDateString(),
                        endDate: $asOfDateCarbon->toDateString(),
                    );

                    if ($category === AccountCategory::Equity && $account->type === AccountType::Equity) {
                        $categoryAccounts[] = $accountDTO;
                    } else {
                        $accountType = $account->type->getPluralLabel();
                        $categoryAccountsByType[$accountType][] = $accountDTO;
                        $subCategoryTotals[$accountType] = ($subCategoryTotals[$accountType] ?? 0) + $endingBalance;
                    }
                }
            }

            if ($category === AccountCategory::Equity) {
                $retainedEarningsAmount = $this->calculateRetainedEarnings($startDateCarbon->toDateTimeString(), $asOfDateCarbon->toDateTimeString())->getAmount();

                $categorySummaryBalances['ending_balance'] += $retainedEarningsAmount;

                $retainedEarningsDTO = new AccountDTO(
                    'Retained Earnings',
                    'RE',
                    null,
                    $this->formatBalances(['ending_balance' => $retainedEarningsAmount]),
                    startDate: $startDateCarbon->toDateString(),
                    endDate: $asOfDateCarbon->toDateString(),
                );

                $categoryAccounts[] = $retainedEarningsDTO;
            }

            $subCategories = [];
            foreach ($categoryAccountsByType as $accountType => $accountsInType) {
                $subCategorySummary = $this->formatBalances([
                    'ending_balance' => $subCategoryTotals[$accountType] ?? 0,
                ]);

                $subCategories[$accountType] = new AccountTypeDTO(
                    accounts: $accountsInType,
                    summary: $subCategorySummary
                );
            }

            $reportTotalBalances[match ($category) {
                AccountCategory::Asset => 'assets',
                AccountCategory::Liability => 'liabilities',
                AccountCategory::Equity => 'equity',
            }] += $categorySummaryBalances['ending_balance'];

            $accountCategories[$category->getPluralLabel()] = new AccountCategoryDTO(
                accounts: $categoryAccounts,
                types: $subCategories,
                summary: $this->formatBalances($categorySummaryBalances),
            );
        }

        $netAssets = $reportTotalBalances['assets'] - $reportTotalBalances['liabilities'];

        $formattedReportTotalBalances = $this->formatBalances(['ending_balance' => $netAssets]);

        return new ReportDTO(
            categories: $accountCategories,
            overallTotal: $formattedReportTotalBalances,
            fields: $columns,
            startDate: $startDateCarbon,
            endDate: $asOfDateCarbon,
        );
    }

    public function buildAgingReport(
        string $asOfDate,
        DocumentEntityType $entityType,
        array $columns = [],
        int $daysPerPeriod = 30,
        int $numberOfPeriods = 4
    ): ReportDTO {
        $asOfDateCarbon = Carbon::parse($asOfDate);

        $documents = $entityType === DocumentEntityType::Client
            ? $this->accountService->getUnpaidClientInvoices($asOfDate)->with(['client:id,name'])->get()->groupBy('client_id')
            : $this->accountService->getUnpaidVendorBills($asOfDate)->with(['vendor:id,name'])->get()->groupBy('vendor_id');

        $categories = [];
        $totalAging = [
            'current' => 0,
        ];
        for ($i = 1; $i <= $numberOfPeriods; $i++) {
            $totalAging["period_{$i}"] = 0;
        }
        $totalAging['over_periods'] = 0;
        $totalAging['total'] = 0;

        /** @var DocumentCollection<int,Invoice|Bill> $entityDocuments */
        foreach ($documents as $entityId => $entityDocuments) {
            $aging = [
                'current' => $entityDocuments
                    ->filter(static fn ($doc) => ($doc->days_overdue ?? 0) <= 0)
                    ->sumMoneyInDefaultCurrency('amount_due'),
            ];

            for ($i = 1; $i <= $numberOfPeriods; $i++) {
                $min = ($i - 1) * $daysPerPeriod;
                $max = $i * $daysPerPeriod;
                $aging["period_{$i}"] = $entityDocuments
                    ->filter(static function ($doc) use ($min, $max) {
                        $days = $doc->days_overdue ?? 0;

                        return $days > $min && $days <= $max;
                    })
                    ->sumMoneyInDefaultCurrency('amount_due');
            }

            $aging['over_periods'] = $entityDocuments
                ->filter(static fn ($doc) => ($doc->days_overdue ?? 0) > ($numberOfPeriods * $daysPerPeriod))
                ->sumMoneyInDefaultCurrency('amount_due');

            $aging['total'] = array_sum($aging);

            foreach ($aging as $bucket => $amount) {
                $totalAging[$bucket] += $amount;
            }

            $entity = $entityDocuments->first()->{$entityType->value};

            $categories[] = new EntityReportDTO(
                name: $entity->name,
                id: $entityId,
                aging: $this->formatBalances($aging, AgingBucketDTO::class, false),
            );
        }

        $totalAging['total'] = array_sum($totalAging);

        return new ReportDTO(
            categories: ['Entities' => $categories],
            agingSummary: $this->formatBalances($totalAging, AgingBucketDTO::class),
            fields: $columns,
            endDate: $asOfDateCarbon,
        );
    }

    public function buildEntityBalanceSummaryReport(string $startDate, string $endDate, DocumentEntityType $entityType, array $columns = []): ReportDTO
    {
        $documents = match ($entityType) {
            DocumentEntityType::Client => Invoice::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotIn('status', [
                    InvoiceStatus::Draft,
                    InvoiceStatus::Void,
                ])
                ->whereNotNull('approved_at')
                ->with(['client:id,name'])
                ->get()
                ->groupBy('client_id'),
            DocumentEntityType::Vendor => Bill::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNot('status', BillStatus::Void)
                ->with(['vendor:id,name'])
                ->get()
                ->groupBy('vendor_id'),
        };

        $entities = [];
        $totalBalance = 0;
        $totalPaidBalance = 0;
        $totalUnpaidBalance = 0;

        /** @var DocumentCollection<int,Invoice|Bill> $entityDocuments */
        foreach ($documents as $entityDocuments) {
            $entityTotalBalance = $entityDocuments->sumMoneyInDefaultCurrency('total');

            $entityPaidBalance = $entityDocuments->sumMoneyInDefaultCurrency('amount_paid');

            $entityUnpaidBalance = match ($entityType) {
                DocumentEntityType::Client => $entityDocuments->whereNot('status', InvoiceStatus::Overpaid)
                    ->sumMoneyInDefaultCurrency('amount_due'),
                DocumentEntityType::Vendor => $entityDocuments->whereIn('status', [BillStatus::Open, BillStatus::Partial, BillStatus::Overdue])
                    ->sumMoneyInDefaultCurrency('amount_due'),
            };

            $totalBalance += $entityTotalBalance;
            $totalPaidBalance += $entityPaidBalance;
            $totalUnpaidBalance += $entityUnpaidBalance;

            $formattedBalances = $this->formatBalances([
                'total_balance' => $entityTotalBalance,
                'paid_balance' => $entityPaidBalance,
                'unpaid_balance' => $entityUnpaidBalance,
            ], EntityBalanceDTO::class);

            $entity = $entityDocuments->first()->{$entityType->value};

            $entities[] = new EntityReportDTO(
                name: $entity->name,
                id: $entity->id,
                balance: $formattedBalances,
            );
        }

        $entityBalanceTotal = $this->formatBalances([
            'total_balance' => $totalBalance,
            'paid_balance' => $totalPaidBalance,
            'unpaid_balance' => $totalUnpaidBalance,
        ], EntityBalanceDTO::class);

        return new ReportDTO(
            categories: ['Entities' => $entities],
            entityBalanceTotal: $entityBalanceTotal,
            fields: $columns,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }

    public function buildEntityPaymentPerformanceReport(
        string $startDate,
        string $endDate,
        DocumentEntityType $entityType,
        array $columns = []
    ): ReportDTO {
        $documents = match ($entityType) {
            DocumentEntityType::Client => Invoice::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Void])
                ->whereNotNull('approved_at')
                ->whereNotNull('paid_at')
                ->with(['client:id,name'])
                ->get()
                ->groupBy('client_id'),
            DocumentEntityType::Vendor => Bill::query()
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNotIn('status', [BillStatus::Void])
                ->whereNotNull('paid_at')
                ->with(['vendor:id,name'])
                ->get()
                ->groupBy('vendor_id'),
        };

        $categories = [];
        $totalDocs = 0;
        $totalOnTime = 0;
        $totalLate = 0;
        $allPaymentDays = [];
        $allLateDays = [];

        /** @var DocumentCollection<int,Invoice|Bill> $entityDocuments */
        foreach ($documents as $entityId => $entityDocuments) {
            $entity = $entityDocuments->first()->{$entityType->value};

            $onTimeDocs = $entityDocuments->filter(fn (Invoice | Bill $doc) => $doc->paid_at->lte($doc->due_date));
            $onTimeCount = $onTimeDocs->count();

            $lateDocs = $entityDocuments->filter(fn (Invoice | Bill $doc) => $doc->paid_at->gt($doc->due_date));
            $lateCount = $lateDocs->count();

            $avgDaysToPay = $entityDocuments->avg(
                fn (Invoice | Bill $doc) => $doc instanceof Invoice
                    ? $doc->approved_at->diffInDays($doc->paid_at)
                    : $doc->date->diffInDays($doc->paid_at)
            ) ?? 0;

            $avgDaysLate = $lateDocs->avg(fn (Invoice | Bill $doc) => $doc->due_date->diffInDays($doc->paid_at)) ?? 0;

            $onTimeRate = $entityDocuments->isNotEmpty()
                ? ($onTimeCount / $entityDocuments->count() * 100)
                : 0;

            $totalDocs += $entityDocuments->count();
            $totalOnTime += $onTimeCount;
            $totalLate += $lateCount;

            $entityDocuments->each(function (Invoice | Bill $doc) use (&$allPaymentDays, &$allLateDays) {
                $allPaymentDays[] = $doc instanceof Invoice
                    ? $doc->approved_at->diffInDays($doc->paid_at)
                    : $doc->date->diffInDays($doc->paid_at);

                if ($doc->paid_at->gt($doc->due_date)) {
                    $allLateDays[] = $doc->due_date->diffInDays($doc->paid_at);
                }
            });

            $categories[] = new EntityReportDTO(
                name: $entity->name,
                id: $entityId,
                paymentMetrics: new PaymentMetricsDTO(
                    totalDocuments: $entityDocuments->count(),
                    onTimeCount: $onTimeCount ?: null,
                    lateCount: $lateCount ?: null,
                    avgDaysToPay: $avgDaysToPay ? round($avgDaysToPay) : null,
                    avgDaysLate: $avgDaysLate ? round($avgDaysLate) : null,
                    onTimePaymentRate: Number::percentage($onTimeRate, maxPrecision: 2),
                ),
            );
        }

        $categories = collect($categories)
            ->sortByDesc(static fn (EntityReportDTO $category) => $category->paymentMetrics->onTimePaymentRate, SORT_NATURAL)
            ->values()
            ->all();

        $overallMetrics = new PaymentMetricsDTO(
            totalDocuments: $totalDocs,
            onTimeCount: $totalOnTime,
            lateCount: $totalLate,
            avgDaysToPay: round(collect($allPaymentDays)->avg() ?? 0),
            avgDaysLate: round(collect($allLateDays)->avg() ?? 0),
            onTimePaymentRate: Number::percentage(
                $totalDocs > 0 ? ($totalOnTime / $totalDocs * 100) : 0,
                maxPrecision: 2
            ),
        );

        return new ReportDTO(
            categories: ['Entities' => $categories],
            overallPaymentMetrics: $overallMetrics,
            fields: $columns,
            startDate: Carbon::parse($startDate),
            endDate: Carbon::parse($endDate),
        );
    }
}
