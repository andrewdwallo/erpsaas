<?php

namespace App\Filament\Company\Resources\Accounting\BudgetResource\Pages;

use App\Enums\Accounting\BudgetIntervalType;
use App\Enums\Accounting\BudgetSourceType;
use App\Facades\Accounting;
use App\Filament\Company\Resources\Accounting\BudgetResource;
use App\Filament\Forms\Components\CustomSection;
use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Models\Accounting\BudgetAllocation;
use App\Models\Accounting\BudgetItem;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CreateBudget extends CreateRecord
{
    use HasWizard;

    protected static string $resource = BudgetResource::class;

    // Add computed properties
    public function getBudgetableAccounts(): Collection
    {
        return $this->getAccountsCache('budgetable', function () {
            return Account::query()->budgetable()->get();
        });
    }

    public function getAccountsWithActuals(): Collection
    {
        $fiscalYear = $this->data['source_fiscal_year'] ?? null;

        if (blank($fiscalYear)) {
            return collect();
        }

        return $this->getAccountsCache("actuals_{$fiscalYear}", function () use ($fiscalYear) {
            return Account::query()
                ->budgetable()
                ->whereHas('journalEntries.transaction', function (Builder $query) use ($fiscalYear) {
                    $query->whereYear('posted_at', $fiscalYear);
                })
                ->get();
        });
    }

    public function getAccountsWithoutActuals(): Collection
    {
        $fiscalYear = $this->data['source_fiscal_year'] ?? null;

        if (blank($fiscalYear)) {
            return collect();
        }

        $budgetableAccounts = $this->getBudgetableAccounts();
        $accountsWithActuals = $this->getAccountsWithActuals();

        return $budgetableAccounts->whereNotIn('id', $accountsWithActuals->pluck('id'));
    }

    public function getAccountBalances(): Collection
    {
        $fiscalYear = $this->data['source_fiscal_year'] ?? null;

        if (blank($fiscalYear)) {
            return collect();
        }

        return $this->getAccountsCache("balances_{$fiscalYear}", function () use ($fiscalYear) {
            $fiscalYearStart = Carbon::create($fiscalYear, 1, 1)->startOfYear();
            $fiscalYearEnd = $fiscalYearStart->copy()->endOfYear();

            return Accounting::getAccountBalances(
                $fiscalYearStart->toDateString(),
                $fiscalYearEnd->toDateString(),
                $this->getBudgetableAccounts()->pluck('id')->toArray()
            )->get();
        });
    }

    // Cache helper to avoid duplicate queries
    private array $accountsCache = [];

    private function getAccountsCache(string $key, callable $callback): Collection
    {
        if (! isset($this->accountsCache[$key])) {
            $this->accountsCache[$key] = $callback();
        }

        return $this->accountsCache[$key];
    }

    public function getSteps(): array
    {
        return [
            Step::make('General Information')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('interval_type')
                        ->label('Budget Interval')
                        ->options(BudgetIntervalType::class)
                        ->default(BudgetIntervalType::Month->value)
                        ->required()
                        ->live(),
                    DatePicker::make('start_date')
                        ->required()
                        ->default(now()->startOfYear())
                        ->live(),
                    DatePicker::make('end_date')
                        ->required()
                        ->default(now()->endOfYear())
                        ->live()
                        ->disabled(static fn (Get $get) => blank($get('start_date')))
                        ->minDate(fn (Get $get) => match (BudgetIntervalType::parse($get('interval_type'))) {
                            BudgetIntervalType::Month => Carbon::parse($get('start_date'))->addMonth(),
                            BudgetIntervalType::Quarter => Carbon::parse($get('start_date'))->addQuarter(),
                            BudgetIntervalType::Year => Carbon::parse($get('start_date'))->addYear(),
                            default => Carbon::parse($get('start_date'))->addDay(),
                        })
                        ->maxDate(fn (Get $get) => Carbon::parse($get('start_date'))->endOfYear()),
                ]),

            Step::make('Budget Setup & Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    // Prefill configuration
                    Toggle::make('prefill_data')
                        ->label('Prefill Data')
                        ->helperText('Enable this option to prefill the budget with historical data')
                        ->default(false)
                        ->live(),

                    Grid::make(1)
                        ->schema([
                            Select::make('source_type')
                                ->label('Prefill Method')
                                ->options(BudgetSourceType::class)
                                ->live()
                                ->required(),

                            // If user selects to copy a previous budget
                            Select::make('source_budget_id')
                                ->label('Source Budget')
                                ->options(fn () => Budget::query()
                                    ->orderByDesc('end_date')
                                    ->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->visible(fn (Get $get) => BudgetSourceType::parse($get('source_type'))?->isBudget()),

                            // If user selects to use historical actuals
                            Select::make('source_fiscal_year')
                                ->label('Fiscal Year')
                                ->options(function () {
                                    $options = [];
                                    $company = auth()->user()->currentCompany;
                                    $earliestDate = Carbon::parse(Accounting::getEarliestTransactionDate());
                                    $fiscalYearStartCurrent = Carbon::parse($company->locale->fiscalYearStartDate());

                                    for ($year = $fiscalYearStartCurrent->year; $year >= $earliestDate->year; $year--) {
                                        $options[$year] = $year;
                                    }

                                    return $options;
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    // Clear the cache when the fiscal year changes
                                    $this->accountsCache = [];

                                    // Get all accounts without actuals
                                    $accountIdsWithoutActuals = $this->getAccountsWithoutActuals()->pluck('id')->toArray();

                                    // Set exclude_accounts_without_actuals to true by default
                                    $set('exclude_accounts_without_actuals', true);

                                    // Update the selected_accounts field to exclude accounts without actuals
                                    $set('selected_accounts', $accountIdsWithoutActuals);
                                })
                                ->visible(fn (Get $get) => BudgetSourceType::parse($get('source_type'))?->isActuals()),
                        ])->visible(fn (Get $get) => $get('prefill_data') === true),

                    CustomSection::make('Account Selection')
                        ->contained(false)
                        ->schema([
                            Checkbox::make('exclude_accounts_without_actuals')
                                ->label('Exclude all accounts without actuals')
                                ->helperText(function () {
                                    $count = $this->getAccountsWithoutActuals()->count();

                                    return "Will exclude {$count} accounts without transaction data in the selected fiscal year";
                                })
                                ->default(true)
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    if ($state) {
                                        // When checked, select all accounts without actuals
                                        $accountsWithoutActuals = $this->getAccountsWithoutActuals()->pluck('id')->toArray();
                                        $set('selected_accounts', $accountsWithoutActuals);
                                    } else {
                                        // When unchecked, clear the selection
                                        $set('selected_accounts', []);
                                    }
                                }),

                            CheckboxList::make('selected_accounts')
                                ->label('Select Accounts to Exclude')
                                ->options(function () {
                                    // Get all budgetable accounts
                                    return $this->getBudgetableAccounts()->pluck('name', 'id')->toArray();
                                })
                                ->descriptions(function (CheckboxList $component) {
                                    $fiscalYear = $this->data['source_fiscal_year'] ?? null;

                                    if (blank($fiscalYear)) {
                                        return [];
                                    }

                                    $accountIds = array_keys($component->getOptions());
                                    $descriptions = [];

                                    if (empty($accountIds)) {
                                        return [];
                                    }

                                    // Get account balances
                                    $accountBalances = $this->getAccountBalances()->keyBy('id');

                                    // Get accounts with actuals
                                    $accountsWithActuals = $this->getAccountsWithActuals()->pluck('id')->toArray();

                                    // Process all accounts
                                    foreach ($accountIds as $accountId) {
                                        $balance = $accountBalances[$accountId] ?? null;
                                        $hasActuals = in_array($accountId, $accountsWithActuals);

                                        if ($balance && $hasActuals) {
                                            // Calculate net movement
                                            $netMovement = Accounting::calculateNetMovementByCategory(
                                                $balance->category,
                                                $balance->total_debit ?? 0,
                                                $balance->total_credit ?? 0
                                            );

                                            // Format the amount for display
                                            $formattedAmount = CurrencyConverter::formatCentsToMoney($netMovement);
                                            $descriptions[$accountId] = "{$formattedAmount} in {$fiscalYear}";
                                        } else {
                                            $descriptions[$accountId] = "No transactions in {$fiscalYear}";
                                        }
                                    }

                                    return $descriptions;
                                })
                                ->columns(2) // Display in two columns
                                ->searchable() // Allow searching for accounts
                                ->bulkToggleable() // Enable "Select All" / "Deselect All"
                                ->selectAllAction(fn (Action $action) => $action->label('Exclude all accounts'))
                                ->deselectAllAction(fn (Action $action) => $action->label('Include all accounts'))
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // Get all accounts without actuals
                                    $accountsWithoutActuals = $this->getAccountsWithoutActuals()->pluck('id')->toArray();

                                    // Check if all accounts without actuals are in the selected accounts
                                    $allAccountsWithoutActualsSelected = empty(array_diff($accountsWithoutActuals, $state));

                                    // Update the exclude_accounts_without_actuals checkbox state
                                    $set('exclude_accounts_without_actuals', $allAccountsWithoutActualsSelected);
                                }),
                        ])
                        ->visible(function (Get $get) {
                            // Only show when using actuals with valid fiscal year AND accounts without transactions exist
                            $prefillSourceType = BudgetSourceType::parse($get('source_type'));

                            if ($prefillSourceType !== BudgetSourceType::Actuals || blank($get('source_fiscal_year'))) {
                                return false;
                            }

                            return $this->getAccountsWithoutActuals()->isNotEmpty();
                        }),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Budget $budget */
        $budget = Budget::create([
            'source_budget_id' => $data['source_budget_id'] ?? null,
            'source_fiscal_year' => $data['source_fiscal_year'] ?? null,
            'source_type' => $data['source_type'] ?? null,
            'name' => $data['name'],
            'interval_type' => $data['interval_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        $selectedAccounts = $data['selected_accounts'] ?? [];

        $accountsToInclude = Account::query()
            ->budgetable()
            ->whereNotIn('id', $selectedAccounts)
            ->get();

        foreach ($accountsToInclude as $account) {
            /** @var BudgetItem $budgetItem */
            $budgetItem = $budget->budgetItems()->create([
                'account_id' => $account->id,
            ]);

            $allocationStart = Carbon::parse($data['start_date']);
            $budgetEndDate = Carbon::parse($data['end_date']);

            // Determine amounts based on the prefill method
            $amounts = match ($data['source_type'] ?? null) {
                'actuals' => $this->getAmountsFromActuals($account, $data['source_fiscal_year'], BudgetIntervalType::parse($data['interval_type'])),
                'previous_budget' => $this->getAmountsFromPreviousBudget($account, $data['source_budget_id'], BudgetIntervalType::parse($data['interval_type'])),
                default => $this->generateZeroAmounts($data['start_date'], $data['end_date'], BudgetIntervalType::parse($data['interval_type'])),
            };

            if (empty($amounts)) {
                $amounts = $this->generateZeroAmounts(
                    $data['start_date'],
                    $data['end_date'],
                    BudgetIntervalType::parse($data['interval_type'])
                );
            }

            foreach ($amounts as $periodLabel => $amount) {
                if ($allocationStart->gt($budgetEndDate)) {
                    break;
                }

                $allocationEnd = self::calculateEndDate($allocationStart, BudgetIntervalType::parse($data['interval_type']));

                if ($allocationEnd->gt($budgetEndDate)) {
                    $allocationEnd = $budgetEndDate->copy();
                }

                $budgetItem->allocations()->create([
                    'period' => $periodLabel,
                    'interval_type' => $data['interval_type'],
                    'start_date' => $allocationStart->toDateString(),
                    'end_date' => $allocationEnd->toDateString(),
                    'amount' => CurrencyConverter::convertCentsToFloat($amount),
                ]);

                $allocationStart = $allocationEnd->addDay();
            }
        }

        return $budget;
    }

    private function getAmountsFromActuals(Account $account, int $fiscalYear, BudgetIntervalType $intervalType): array
    {
        $amounts = [];

        // Get the start and end date of the budget being created
        $budgetStartDate = Carbon::parse($this->data['start_date']);
        $budgetEndDate = Carbon::parse($this->data['end_date']);

        // Map to equivalent dates in the reference fiscal year
        $referenceStartDate = Carbon::create($fiscalYear, $budgetStartDate->month, $budgetStartDate->day);
        $referenceEndDate = Carbon::create($fiscalYear, $budgetEndDate->month, $budgetEndDate->day);

        // Handle year boundary case (if budget crosses year boundary)
        if ($budgetStartDate->month > $budgetEndDate->month ||
            ($budgetStartDate->month === $budgetEndDate->month && $budgetStartDate->day > $budgetEndDate->day)) {
            $referenceEndDate->year++;
        }

        if ($intervalType->isMonth()) {
            // Process month by month within the reference period
            $currentDate = $referenceStartDate->copy()->startOfMonth();
            $lastMonth = $referenceEndDate->copy()->startOfMonth();

            while ($currentDate->lte($lastMonth)) {
                $periodStart = $currentDate->copy()->startOfMonth();
                $periodEnd = $currentDate->copy()->endOfMonth();
                $periodLabel = $this->determinePeriod($periodStart, $intervalType);

                $netMovement = Accounting::getNetMovement(
                    $account,
                    $periodStart->toDateString(),
                    $periodEnd->toDateString()
                );

                $amounts[$periodLabel] = $netMovement->getAmount();

                $currentDate->addMonth();
            }
        } elseif ($intervalType->isQuarter()) {
            // Process quarter by quarter within the reference period
            $currentDate = $referenceStartDate->copy()->startOfQuarter();
            $lastQuarter = $referenceEndDate->copy()->startOfQuarter();

            while ($currentDate->lte($lastQuarter)) {
                $periodStart = $currentDate->copy()->startOfQuarter();
                $periodEnd = $currentDate->copy()->endOfQuarter();
                $periodLabel = $this->determinePeriod($periodStart, $intervalType);

                $netMovement = Accounting::getNetMovement(
                    $account,
                    $periodStart->toDateString(),
                    $periodEnd->toDateString()
                );

                $amounts[$periodLabel] = $netMovement->getAmount();

                $currentDate->addQuarter();
            }
        } else {
            // For yearly intervals
            $periodStart = $referenceStartDate->copy()->startOfYear();
            $periodEnd = $referenceEndDate->copy()->endOfYear();
            $periodLabel = $this->determinePeriod($periodStart, $intervalType);

            $netMovement = Accounting::getNetMovement(
                $account,
                $periodStart->toDateString(),
                $periodEnd->toDateString()
            );

            $amounts[$periodLabel] = $netMovement->getAmount();
        }

        return $amounts;
    }

    private function distributeAmountAcrossPeriods(int $totalAmountInCents, Carbon $startDate, Carbon $endDate, BudgetIntervalType $intervalType): array
    {
        $amounts = [];
        $periods = [];

        // Generate period labels based on interval type
        $currentPeriod = $startDate->copy();
        while ($currentPeriod->lte($endDate)) {
            $periods[] = $this->determinePeriod($currentPeriod, $intervalType);
            $currentPeriod->addUnit($intervalType->value);
        }

        // Evenly distribute total amount across periods
        $periodCount = count($periods);

        if ($periodCount === 0) {
            return $amounts;
        }

        $baseAmount = intdiv($totalAmountInCents, $periodCount); // Floor division to get the base amount in cents
        $remainder = $totalAmountInCents % $periodCount; // Remaining cents to distribute

        foreach ($periods as $index => $period) {
            $amounts[$period] = $baseAmount + ($index < $remainder ? 1 : 0); // Distribute remainder cents evenly
        }

        return $amounts;
    }

    private function getAmountsFromPreviousBudget(Account $account, int $sourceBudgetId, BudgetIntervalType $intervalType): array
    {
        $amounts = [];

        // Get the budget being created start and end dates
        $newBudgetStartDate = Carbon::parse($this->data['start_date']);
        $newBudgetEndDate = Carbon::parse($this->data['end_date']);

        // Get source budget's date information
        $sourceBudget = Budget::findOrFail($sourceBudgetId);
        $sourceBudgetType = $sourceBudget->interval_type;

        // Retrieve all previous allocations for this account
        $previousAllocations = BudgetAllocation::query()
            ->whereHas(
                'budgetItem',
                fn ($query) => $query->where('account_id', $account->id)
                    ->where('budget_id', $sourceBudgetId)
            )
            ->orderBy('start_date')
            ->get();

        if ($previousAllocations->isEmpty()) {
            return $this->generateZeroAmounts(
                $this->data['start_date'],
                $this->data['end_date'],
                $intervalType
            );
        }

        // Map previous budget periods to current budget periods
        if ($intervalType === $sourceBudgetType) {
            // Same interval type: direct mapping of equivalent periods
            foreach ($previousAllocations as $allocation) {
                $allocationDate = Carbon::parse($allocation->start_date);

                // Create an equivalent date in the new budget's time range
                $equivalentMonth = $allocationDate->month;
                $equivalentDay = $allocationDate->day;
                $equivalentYear = $newBudgetStartDate->year;

                // Adjust year if the budget spans multiple years
                if ($newBudgetStartDate->month > $newBudgetEndDate->month &&
                    $equivalentMonth < $newBudgetStartDate->month) {
                    $equivalentYear++;
                }

                $equivalentDate = Carbon::create($equivalentYear, $equivalentMonth, $equivalentDay);

                // Only include if the date falls within our new budget period
                if ($equivalentDate->between($newBudgetStartDate, $newBudgetEndDate)) {
                    $periodLabel = $this->determinePeriod($equivalentDate, $intervalType);
                    $amounts[$periodLabel] = $allocation->getRawOriginal('amount');
                }
            }
        } else {
            // Handle conversion between different interval types
            $newBudgetPeriods = $this->generateZeroAmounts(
                $this->data['start_date'],
                $this->data['end_date'],
                $intervalType
            );

            // Fill with zeros initially
            $amounts = array_fill_keys(array_keys($newBudgetPeriods), 0);

            // Group previous allocations by their date range
            $allocationsByRange = [];
            foreach ($previousAllocations as $allocation) {
                $allocationsByRange[] = [
                    'start' => Carbon::parse($allocation->start_date),
                    'end' => Carbon::parse($allocation->end_date),
                    'amount' => $allocation->getRawOriginal('amount'),
                ];
            }

            // Create new allocations based on interval type
            $currentDate = Carbon::parse($this->data['start_date']);
            $endDate = Carbon::parse($this->data['end_date']);

            while ($currentDate->lte($endDate)) {
                $periodStart = $currentDate->copy();
                $periodEnd = self::calculateEndDate($currentDate, $intervalType);

                if ($periodEnd->gt($endDate)) {
                    $periodEnd = $endDate->copy();
                }

                $periodLabel = $this->determinePeriod($periodStart, $intervalType);

                // Calculate the proportional amount from the source budget
                $weightedAmount = 0;

                foreach ($allocationsByRange as $allocation) {
                    // Find overlapping days between new period and source allocation
                    $overlapStart = max($periodStart, $allocation['start']);
                    $overlapEnd = min($periodEnd, $allocation['end']);

                    if ($overlapStart <= $overlapEnd) {
                        // Calculate overlapping days
                        $overlapDays = $overlapStart->diffInDays($overlapEnd) + 1;
                        $allocationTotalDays = $allocation['start']->diffInDays($allocation['end']) + 1;

                        // Calculate proportional amount based on days
                        $proportion = $overlapDays / $allocationTotalDays;
                        $proportionalAmount = (int) ($allocation['amount'] * $proportion);

                        $weightedAmount += $proportionalAmount;
                    }
                }

                // Assign the calculated amount to the period
                if (array_key_exists($periodLabel, $amounts)) {
                    $amounts[$periodLabel] = $weightedAmount;
                }

                // Move to the next period
                $currentDate = $periodEnd->copy()->addDay();
            }
        }

        return $amounts;
    }

    private function generateZeroAmounts(string $startDate, string $endDate, BudgetIntervalType $intervalType): array
    {
        $amounts = [];

        $currentPeriod = Carbon::parse($startDate);
        while ($currentPeriod->lte(Carbon::parse($endDate))) {
            $period = $this->determinePeriod($currentPeriod, $intervalType);
            $amounts[$period] = 0;
            $currentPeriod->addUnit($intervalType->value);
        }

        return $amounts;
    }

    private function determinePeriod(Carbon $date, BudgetIntervalType $intervalType): string
    {
        return match ($intervalType) {
            BudgetIntervalType::Month => $date->format('M Y'),
            BudgetIntervalType::Quarter => 'Q' . $date->quarter . ' ' . $date->year,
            BudgetIntervalType::Year => (string) $date->year,
        };
    }

    private static function calculateEndDate(Carbon $startDate, BudgetIntervalType $intervalType): Carbon
    {
        return match ($intervalType) {
            BudgetIntervalType::Month => $startDate->copy()->endOfMonth(),
            BudgetIntervalType::Quarter => $startDate->copy()->endOfQuarter(),
            BudgetIntervalType::Year => $startDate->copy()->endOfYear(),
        };
    }
}
