<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Widgets\LineChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Models\Expense;
use App\Charts\MonthlyRevenueChart;
use App\Models\ExpenseTransaction;

class ExpenseOverview extends LineChartWidget
{

    public ?string $filter = 'today';

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'Last week',
            'month' => 'Last month',
            'year' => 'This year',
        ];
    }

    protected function getHeading(): string
    {
        return 'Total Expense';
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $trend = Trend::model(ExpenseTransaction::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Expense',
                    'data' => $trend->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $trend->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
