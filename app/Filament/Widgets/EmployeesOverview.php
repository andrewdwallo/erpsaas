<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\Widget;
use Filament\Widgets\LineChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class EmployeesOverview extends LineChartWidget
{
    protected function getHeading(): string
    {
        return 'Employees Overview';
    }

    protected function getData(): array
    {
        $data = Trend::model(Employee::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->count('*');

        return [
            'datasets' => [
                [
                    'label ' => 'Employees',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
