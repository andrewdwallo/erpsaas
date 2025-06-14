<?php

namespace App\Filament\Company\Resources\Accounting;

use App\Enums\Accounting\BudgetIntervalType;
use App\Filament\Company\Resources\Accounting\BudgetResource\Pages\CreateBudget;
use App\Filament\Company\Resources\Accounting\BudgetResource\Pages\ListBudgets;
use App\Filament\Company\Resources\Accounting\BudgetResource\Pages\ViewBudget;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\CustomTableRepeater;
use App\Models\Accounting\Account;
use App\Models\Accounting\Budget;
use App\Models\Accounting\BudgetAllocation;
use App\Models\Accounting\BudgetItem;
use App\Utilities\Currency\CurrencyConverter;
use Awcodes\TableRepeater\Header;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Budget Details')
                    ->columns()
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
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),

                //                Forms\Components\Section::make('Budget Items')
                //                    ->headerActions([
                //                        Forms\Components\Actions\Action::make('addAccounts')
                //                            ->label('Add Accounts')
                //                            ->icon('heroicon-m-plus')
                //                            ->outlined()
                //                            ->color('primary')
                //                            ->form(fn (Forms\Get $get) => [
                //                                Forms\Components\Select::make('selected_accounts')
                //                                    ->label('Choose Accounts to Add')
                //                                    ->options(function () use ($get) {
                //                                        $existingAccounts = collect($get('budgetItems'))->pluck('account_id')->toArray();
                //
                //                                        return Account::query()
                //                                            ->budgetable()
                //                                            ->whereNotIn('id', $existingAccounts) // Prevent duplicate selections
                //                                            ->pluck('name', 'id');
                //                                    })
                //                                    ->searchable()
                //                                    ->multiple()
                //                                    ->hint('Select the accounts you want to add to this budget'),
                //                            ])
                //                            ->action(static fn (Forms\Set $set, Forms\Get $get, array $data) => self::addSelectedAccounts($set, $get, $data)),
                //
                //                        Forms\Components\Actions\Action::make('addAllAccounts')
                //                            ->label('Add All Accounts')
                //                            ->icon('heroicon-m-folder-plus')
                //                            ->outlined()
                //                            ->color('primary')
                //                            ->action(static fn (Forms\Set $set, Forms\Get $get) => self::addAllAccounts($set, $get))
                //                            ->hidden(static fn (Forms\Get $get) => filled($get('budgetItems'))),
                //
                //                        Forms\Components\Actions\Action::make('increaseAllocations')
                //                            ->label('Increase Allocations')
                //                            ->icon('heroicon-m-arrow-up')
                //                            ->outlined()
                //                            ->color('success')
                //                            ->form(fn (Forms\Get $get) => [
                //                                Forms\Components\Select::make('increase_type')
                //                                    ->label('Increase Type')
                //                                    ->options([
                //                                        'percentage' => 'Percentage (%)',
                //                                        'fixed' => 'Fixed Amount',
                //                                    ])
                //                                    ->default('percentage')
                //                                    ->live()
                //                                    ->required(),
                //
                //                                Forms\Components\TextInput::make('percentage')
                //                                    ->label('Increase by %')
                //                                    ->numeric()
                //                                    ->suffix('%')
                //                                    ->required()
                //                                    ->hidden(fn (Forms\Get $get) => $get('increase_type') !== 'percentage'),
                //
                //                                Forms\Components\TextInput::make('fixed_amount')
                //                                    ->label('Increase by Fixed Amount')
                //                                    ->numeric()
                //                                    ->suffix('USD')
                //                                    ->required()
                //                                    ->hidden(fn (Forms\Get $get) => $get('increase_type') !== 'fixed'),
                //
                //                                Forms\Components\Select::make('apply_to_accounts')
                //                                    ->label('Apply to Accounts')
                //                                    ->options(function () use ($get) {
                //                                        $budgetItems = $get('budgetItems') ?? [];
                //                                        $accountIds = collect($budgetItems)
                //                                            ->pluck('account_id')
                //                                            ->filter()
                //                                            ->unique()
                //                                            ->toArray();
                //
                //                                        return Account::query()
                //                                            ->whereIn('id', $accountIds)
                //                                            ->pluck('name', 'id')
                //                                            ->toArray();
                //                                    })
                //                                    ->searchable()
                //                                    ->multiple()
                //                                    ->hint('Leave blank to apply to all accounts'),
                //
                //                                Forms\Components\Select::make('apply_to_periods')
                //                                    ->label('Apply to Periods')
                //                                    ->options(static function () use ($get) {
                //                                        $startDate = $get('start_date');
                //                                        $endDate = $get('end_date');
                //                                        $intervalType = $get('interval_type');
                //
                //                                        if (blank($startDate) || blank($endDate) || blank($intervalType)) {
                //                                            return [];
                //                                        }
                //
                //                                        $labels = self::generateFormattedLabels($startDate, $endDate, $intervalType);
                //
                //                                        return array_combine($labels, $labels);
                //                                    })
                //                                    ->searchable()
                //                                    ->multiple()
                //                                    ->hint('Leave blank to apply to all periods'),
                //                            ])
                //                            ->action(static fn (Forms\Set $set, Forms\Get $get, array $data) => self::increaseAllocations($set, $get, $data))
                //                            ->visible(static fn (Forms\Get $get) => filled($get('budgetItems'))),
                //                    ])
                //                    ->schema([
                //                        Forms\Components\Repeater::make('budgetItems')
                //                            ->columns(4)
                //                            ->hiddenLabel()
                //                            ->schema([
                //                                Forms\Components\Select::make('account_id')
                //                                    ->label('Account')
                //                                    ->options(Account::query()
                //                                        ->budgetable()
                //                                        ->pluck('name', 'id'))
                //                                    ->searchable()
                //                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                //                                    ->columnSpan(1)
                //                                    ->required(),
                //
                //                                Forms\Components\TextInput::make('total_amount')
                //                                    ->label('Total Amount')
                //                                    ->numeric()
                //                                    ->columnSpan(1)
                //                                    ->suffixAction(
                //                                        Forms\Components\Actions\Action::make('disperse')
                //                                            ->label('Disperse')
                //                                            ->icon('heroicon-m-bars-arrow-down')
                //                                            ->color('primary')
                //                                            ->action(static fn (Forms\Set $set, Forms\Get $get, $state) => self::disperseTotalAmount($set, $get, $state))
                //                                    ),
                //
                //                                CustomSection::make('Budget Allocations')
                //                                    ->contained(false)
                //                                    ->columns(4)
                //                                    ->schema(static fn (Forms\Get $get) => self::getAllocationFields($get('../../start_date'), $get('../../end_date'), $get('../../interval_type'))),
                //                            ])
                //                            ->defaultItems(0)
                //                            ->addActionLabel('Add Budget Item'),
                //                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge(),

                TextColumn::make('interval_type')
                    ->label('Interval')
                    ->sortable()
                    ->badge(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make('editAllocations')
                        ->name('editAllocations')
                        ->url(null)
                        ->label('Edit Allocations')
                        ->icon('heroicon-o-table-cells')
                        ->modalWidth(Width::Screen)
                        ->modalHeading('Edit Budget Allocations')
                        ->modalDescription('Update the allocations for this budget')
                        ->slideOver()
                        ->schema(function (Budget $record) {
                            $periods = $record->getPeriods();

                            $headers = [
                                Header::make('Account')
                                    ->label('Account')
                                    ->width('200px'),
                                Header::make('total')
                                    ->label('Total')
                                    ->width('120px')
                                    ->align(Alignment::Right),
                                Header::make('action')
                                    ->label('')
                                    ->width('40px')
                                    ->align(Alignment::Center),
                            ];

                            foreach ($periods as $period) {
                                $headers[] = Header::make($period->period)
                                    ->label($period->period)
                                    ->width('120px')
                                    ->align(Alignment::Right);
                            }

                            return [
                                CustomTableRepeater::make('budgetItems')
                                    ->relationship()
                                    ->hiddenLabel()
                                    ->headers($headers)
                                    ->schema([
                                        Placeholder::make('account')
                                            ->hiddenLabel()
                                            ->content(fn (BudgetItem $record) => $record->account->name ?? ''),

                                        TextInput::make('total')
                                            ->hiddenLabel()
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->numeric()
                                            ->afterStateHydrated(function ($component, $state, BudgetItem $record) use ($periods) {
                                                $total = 0;
                                                // Calculate the total for this budget item across all periods
                                                foreach ($periods as $period) {
                                                    $allocation = $record->allocations->firstWhere('period', $period->period);
                                                    $total += $allocation ? $allocation->getRawOriginal('amount') : 0;
                                                }
                                                $component->state(CurrencyConverter::convertCentsToFormatSimple($total));
                                            })
                                            ->dehydrated(false),

                                        Actions::make([
                                            Action::make('disperse')
                                                ->label('Disperse')
                                                ->icon('heroicon-m-chevron-double-right')
                                                ->color('primary')
                                                ->iconButton()
                                                ->action(function (Set $set, Get $get, BudgetItem $record, $livewire) use ($periods) {
                                                    $total = CurrencyConverter::convertToCents($get('total'));
                                                    $numPeriods = count($periods);

                                                    if ($numPeriods === 0) {
                                                        return;
                                                    }

                                                    $baseAmount = floor($total / $numPeriods);
                                                    $remainder = $total - ($baseAmount * $numPeriods);

                                                    foreach ($periods as $index => $period) {
                                                        $amount = $baseAmount + ($index === 0 ? $remainder : 0);
                                                        $formattedAmount = CurrencyConverter::convertCentsToFormatSimple($amount);
                                                        $set("allocations.{$period->period}", $formattedAmount);
                                                    }
                                                }),
                                        ]),

                                        // Create a field for each period
                                        ...collect($periods)->map(function (BudgetAllocation $period) {
                                            return TextInput::make("allocations.{$period->period}")
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->numeric()
                                                ->afterStateHydrated(function ($component, $state, BudgetItem $record) use ($period) {
                                                    // Find the allocation for this period
                                                    $allocation = $record->allocations->firstWhere('period', $period->period);
                                                    $component->state($allocation ? $allocation->amount : 0);
                                                })
                                                ->dehydrated(false); // We'll handle saving manually
                                        })->toArray(),
                                    ])
                                    ->spreadsheet()
                                    ->itemLabel(fn (BudgetItem $record) => $record->account->name ?? 'Budget Item')
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->addable(false) // Don't allow adding new budget items
                                    ->columnSpanFull(),
                            ];
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function addAllAccounts(Set $set, Get $get): void
    {
        $accounts = Account::query()
            ->budgetable()
            ->pluck('id');

        $budgetItems = $accounts->map(static fn ($accountId) => [
            'account_id' => $accountId,
            'total_amount' => 0, // Default to 0 until the user inputs amounts
            'amounts' => self::generateDefaultAllocations($get('start_date'), $get('end_date'), $get('interval_type')),
        ])->toArray();

        $set('budgetItems', $budgetItems);
    }

    private static function addSelectedAccounts(Set $set, Get $get, array $data): void
    {
        $selectedAccountIds = $data['selected_accounts'] ?? [];

        if (empty($selectedAccountIds)) {
            return; // No accounts selected, do nothing.
        }

        $existingAccountIds = collect($get('budgetItems'))
            ->pluck('account_id')
            ->unique()
            ->filter()
            ->toArray();

        // Only add accounts that aren't already in the budget items
        $newAccounts = array_diff($selectedAccountIds, $existingAccountIds);

        $newBudgetItems = collect($newAccounts)->map(static fn ($accountId) => [
            'account_id' => $accountId,
            'total_amount' => 0,
            'amounts' => self::generateDefaultAllocations($get('start_date'), $get('end_date'), $get('interval_type')),
        ])->toArray();

        // Merge new budget items with existing ones
        $set('budgetItems', array_merge($get('budgetItems') ?? [], $newBudgetItems));
    }

    private static function generateDefaultAllocations(?string $startDate, ?string $endDate, ?string $intervalType): array
    {
        if (! $startDate || ! $endDate || ! $intervalType) {
            return [];
        }

        $labels = self::generateFormattedLabels($startDate, $endDate, $intervalType);

        return collect($labels)->mapWithKeys(static fn ($label) => [$label => 0])->toArray();
    }

    private static function increaseAllocations(Set $set, Get $get, array $data): void
    {
        $increaseType = $data['increase_type']; // 'percentage' or 'fixed'
        $percentage = $data['percentage'] ?? 0;
        $fixedAmount = $data['fixed_amount'] ?? 0;

        $selectedAccounts = $data['apply_to_accounts'] ?? []; // Selected account IDs
        $selectedPeriods = $data['apply_to_periods'] ?? []; // Selected period labels

        $budgetItems = $get('budgetItems') ?? [];

        foreach ($budgetItems as $index => $budgetItem) {
            // Skip if this account isn't selected (unless all accounts are being updated)
            if (! empty($selectedAccounts) && ! in_array($budgetItem['account_id'], $selectedAccounts)) {
                continue;
            }

            if (empty($budgetItem['amounts'])) {
                continue; // Skip if no allocations exist
            }

            $updatedAmounts = $budgetItem['amounts']; // Clone existing amounts
            foreach ($updatedAmounts as $label => $amount) {
                // Skip if this period isn't selected (unless all periods are being updated)
                if (! empty($selectedPeriods) && ! in_array($label, $selectedPeriods)) {
                    continue;
                }

                // Apply increase based on selected type
                $updatedAmounts[$label] = match ($increaseType) {
                    'percentage' => round($amount * (1 + $percentage / 100), 2),
                    'fixed' => round($amount + $fixedAmount, 2),
                    default => $amount,
                };
            }

            $set("budgetItems.{$index}.amounts", $updatedAmounts);
            $set("budgetItems.{$index}.total_amount", round(array_sum($updatedAmounts), 2));
        }
    }

    private static function disperseTotalAmount(Set $set, Get $get, float $totalAmount): void
    {
        $startDate = $get('../../start_date');
        $endDate = $get('../../end_date');
        $intervalType = $get('../../interval_type');

        if (! $startDate || ! $endDate || ! $intervalType || $totalAmount <= 0) {
            return;
        }

        $labels = self::generateFormattedLabels($startDate, $endDate, $intervalType);
        $numPeriods = count($labels);

        if ($numPeriods === 0) {
            return;
        }

        $baseAmount = floor($totalAmount / $numPeriods);
        $remainder = $totalAmount - ($baseAmount * $numPeriods);

        foreach ($labels as $index => $label) {
            $amount = $baseAmount + ($index === 0 ? $remainder : 0);
            $set("amounts.{$label}", $amount);
        }
    }

    private static function generateFormattedLabels(string $startDate, string $endDate, string $intervalType): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $intervalTypeEnum = BudgetIntervalType::parse($intervalType);
        $labels = [];

        while ($start->lte($end)) {
            $labels[] = match ($intervalTypeEnum) {
                BudgetIntervalType::Month => $start->format('M'), // Example: Jan, Feb, Mar
                BudgetIntervalType::Quarter => 'Q' . $start->quarter, // Example: Q1, Q2, Q3
                BudgetIntervalType::Year => (string) $start->year, // Example: 2024, 2025
                default => '',
            };

            match ($intervalTypeEnum) {
                BudgetIntervalType::Month => $start->addMonth(),
                BudgetIntervalType::Quarter => $start->addQuarter(),
                BudgetIntervalType::Year => $start->addYear(),
                default => null,
            };
        }

        return $labels;
    }

    private static function getAllocationFields(?string $startDate, ?string $endDate, ?string $intervalType): array
    {
        if (! $startDate || ! $endDate || ! $intervalType) {
            return [];
        }

        $fields = [];

        $labels = self::generateFormattedLabels($startDate, $endDate, $intervalType);

        foreach ($labels as $label) {
            $fields[] = TextInput::make("amounts.{$label}")
                ->label($label)
                ->numeric()
                ->required();
        }

        return $fields;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'view' => ViewBudget::route('/{record}'),
        ];
    }
}
