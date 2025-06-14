<?php

namespace App\Filament\Company\Resources\Accounting\BudgetResource\RelationManagers;

use App\Filament\Tables\Columns\CustomTextInputColumn;
use App\Models\Accounting\Budget;
use App\Models\Accounting\BudgetAllocation;
use App\Models\Accounting\BudgetItem;
use App\Utilities\Currency\CurrencyConverter;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

class BudgetItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetItems';

    protected static bool $isLazy = false;

    protected const TOTAL_COLUMN = 'total';

    public array $batchChanges = [];

    /**
     * Generate a consistent key for the budget item and period
     */
    protected static function generatePeriodKey(int $recordId, string | Carbon $startDate): string
    {
        $formattedDate = $startDate instanceof Carbon
            ? $startDate->format('Y_m_d')
            : Carbon::parse($startDate)->format('Y_m_d');

        return "{$recordId}.{$formattedDate}";
    }

    /**
     * Generate a consistent key for the budget item's total
     */
    protected static function generateTotalKey(int $recordId): string
    {
        return "{$recordId}." . self::TOTAL_COLUMN;
    }

    public function handleBatchColumnChanged($data): void
    {
        $key = "{$data['recordKey']}.{$data['name']}";
        $this->batchChanges[$key] = $data['value'];
    }

    public function saveBatchChanges(): void
    {
        foreach ($this->batchChanges as $key => $value) {
            [$recordKey, $column] = explode('.', $key, 2);

            try {
                $startDate = Carbon::createFromFormat('Y_m_d', $column);
            } catch (Exception) {
                continue;
            }

            $record = BudgetItem::find($recordKey);
            if (! $record) {
                continue;
            }

            $allocation = $record->allocations()
                ->whereDate('start_date', $startDate)
                ->first();

            $allocation?->update(['amount' => $value]);
        }

        $this->batchChanges = [];

        Notification::make()
            ->title('Budget allocations updated')
            ->success()
            ->send();
    }

    protected function calculatePeriodSum(array $budgetItemIds, string | Carbon $startDate): int
    {
        $allocations = DB::table('budget_allocations')
            ->whereIn('budget_item_id', $budgetItemIds)
            ->whereDate('start_date', $startDate)
            ->pluck('amount', 'budget_item_id');

        $dbTotal = $allocations->sum();

        $batchTotal = 0;

        foreach ($budgetItemIds as $itemId) {
            $key = self::generatePeriodKey($itemId, $startDate);

            if (isset($this->batchChanges[$key])) {
                $batchValue = CurrencyConverter::convertToCents($this->batchChanges[$key]);
                $existingAmount = $allocations[$itemId] ?? 0;

                $batchTotal += ($batchValue - $existingAmount);
            }
        }

        return $dbTotal + $batchTotal;
    }

    public function table(Table $table): Table
    {
        /** @var Budget $budget */
        $budget = $this->getOwnerRecord();
        $allocationPeriods = $budget->getPeriods();

        return $table
            ->recordTitleAttribute('account_id')
            ->paginated(false)
            ->heading(null)
            ->modifyQueryUsing(function (Builder $query) use ($allocationPeriods) {
                $query->select('budget_items.*')
                    ->leftJoin('budget_allocations', 'budget_allocations.budget_item_id', '=', 'budget_items.id');

                foreach ($allocationPeriods as $period) {
                    $alias = $period->start_date->format('Y_m_d');
                    $query->selectRaw(
                        "SUM(CASE WHEN budget_allocations.start_date = ? THEN budget_allocations.amount ELSE 0 END) as {$alias}",
                        [$period->start_date->toDateString()]
                    );
                }

                return $query->groupBy('budget_items.id');
            })
            ->groups([
                Group::make('account.category')
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->recordClasses(['is-spreadsheet'])
            ->defaultGroup('account.category')
            ->headerActions([
                Action::make('saveBatchChanges')
                    ->label('Save all changes')
                    ->action('saveBatchChanges')
                    ->color('primary'),
            ])
            ->columns([
                TextColumn::make('account.name')
                    ->label('Account')
                    ->limit(30)
                    ->searchable(),
                CustomTextInputColumn::make(self::TOTAL_COLUMN)
                    ->label('Total')
                    ->alignRight()
                    ->mask(RawJs::make('$money($input)'))
                    ->getStateUsing(function (BudgetItem $record) {
                        $key = self::generateTotalKey($record->getKey());
                        if (isset($this->batchChanges[$key])) {
                            return $this->batchChanges[$key];
                        }

                        $total = $record->allocations->sum(
                            fn (BudgetAllocation $allocation) => $allocation->getRawOriginal('amount')
                        );

                        return CurrencyConverter::convertCentsToFormatSimple($total);
                    })
                    ->deferred()
                    ->navigable()
                    ->summarize(
                        Summarizer::make()
                            ->using(function (\Illuminate\Database\Query\Builder $query) {
                                $allocations = $query
                                    ->leftJoin('budget_allocations', 'budget_allocations.budget_item_id', '=', 'budget_items.id')
                                    ->select('budget_allocations.budget_item_id', 'budget_allocations.start_date', 'budget_allocations.amount')
                                    ->get();

                                $allocationsByDate = $allocations->groupBy('start_date');

                                $total = 0;

                                /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, stdClass>> $allocationsByDate */
                                foreach ($allocationsByDate as $startDate => $group) {
                                    $dbTotal = $group->sum('amount');
                                    $amounts = $group->pluck('amount', 'budget_item_id');
                                    $batchTotal = 0;

                                    foreach ($amounts as $itemId => $existingAmount) {
                                        $key = self::generatePeriodKey($itemId, $startDate);

                                        if (isset($this->batchChanges[$key])) {
                                            $batchValue = CurrencyConverter::convertToCents($this->batchChanges[$key]);
                                            $batchTotal += ($batchValue - $existingAmount);
                                        }
                                    }

                                    $total += $dbTotal + $batchTotal;
                                }

                                return CurrencyConverter::convertCentsToFormatSimple($total);
                            })
                    ),
                IconColumn::make('disperseAction')
                    ->icon('heroicon-m-chevron-double-right')
                    ->color('primary')
                    ->label('')
                    ->default('')
                    ->tooltip('Disperse total across periods')
                    ->action(
                        Action::make('disperse')
                            ->label('Disperse')
                            ->action(function (BudgetItem $record) use ($allocationPeriods) {
                                if (empty($allocationPeriods)) {
                                    return;
                                }

                                $totalKey = self::generateTotalKey($record->getKey());
                                $totalAmount = $this->batchChanges[$totalKey] ?? null;

                                if (isset($totalAmount)) {
                                    $totalCents = CurrencyConverter::convertToCents($totalAmount);
                                } else {
                                    $totalCents = $record->allocations->sum(function (BudgetAllocation $budgetAllocation) {
                                        return $budgetAllocation->getRawOriginal('amount');
                                    });
                                }

                                if ($totalCents <= 0) {
                                    foreach ($allocationPeriods as $period) {
                                        $periodKey = self::generatePeriodKey($record->getKey(), $period->start_date);
                                        $this->batchChanges[$periodKey] = CurrencyConverter::convertCentsToFormatSimple(0);
                                    }

                                    return;
                                }

                                $numPeriods = count($allocationPeriods);

                                $baseAmount = floor($totalCents / $numPeriods);
                                $remainder = $totalCents - ($baseAmount * $numPeriods);

                                foreach ($allocationPeriods as $index => $period) {
                                    $amount = $baseAmount + ($index === 0 ? $remainder : 0);
                                    $formattedAmount = CurrencyConverter::convertCentsToFormatSimple($amount);

                                    $periodKey = self::generatePeriodKey($record->getKey(), $period->start_date);
                                    $this->batchChanges[$periodKey] = $formattedAmount;
                                }
                            }),
                    ),
                ...$allocationPeriods->map(function (BudgetAllocation $period) {
                    $alias = $period->start_date->format('Y_m_d');

                    return CustomTextInputColumn::make($alias)
                        ->label($period->period)
                        ->alignRight()
                        ->deferred()
                        ->navigable()
                        ->mask(RawJs::make('$money($input)'))
                        ->getStateUsing(function ($record) use ($alias) {
                            $key = "{$record->getKey()}.{$alias}";

                            return $this->batchChanges[$key] ?? CurrencyConverter::convertCentsToFormatSimple($record->{$alias} ?? 0);
                        })
                        ->summarize(
                            Summarizer::make()
                                ->using(function (\Illuminate\Database\Query\Builder $query) use ($period) {
                                    $budgetItemIds = $query->pluck('id')->toArray();
                                    $total = $this->calculatePeriodSum($budgetItemIds, $period->start_date);

                                    return CurrencyConverter::convertCentsToFormatSimple($total);
                                })
                        );
                })->toArray(),
            ])
            ->toolbarActions([
                BulkAction::make('clearAllocations')
                    ->label('Clear Allocations')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) use ($allocationPeriods) {
                        foreach ($records as $record) {
                            foreach ($allocationPeriods as $period) {
                                $periodKey = self::generatePeriodKey($record->getKey(), $period->start_date);
                                $this->batchChanges[$periodKey] = CurrencyConverter::convertCentsToFormatSimple(0);
                            }
                        }
                    }),
            ]);
    }
}
