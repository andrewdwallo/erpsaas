<?php

namespace App\Filament\Resources\RevenueResource\Widgets;

use Filament\Widgets\LineChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use App\Models\Revenue;
use Filament\Widgets\Widget;
use App\Charts\MonthlyRevenueChart;
use App\Models\IncomeTransaction;

class RevenueOverview extends LineChartWidget
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
        return 'Total Revenue';
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        $trend = Trend::model(IncomeTransaction::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Total Revenue',
                    'data' => $trend->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $trend->map(fn (TrendValue $value) => $value->date),
        ];
    }
}

