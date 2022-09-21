<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Widgets\Widget;
use Filament\Widgets\LineChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class CompaniesOverview extends LineChartWidget
{
    protected function getHeading(): string
    {
        return 'Companies Overview';
    }

    protected function getData(): array
    {
        $data = Trend::model(Company::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->count('*');

        return [
            'datasets' => [
                [
                    'label ' => 'Companies',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
