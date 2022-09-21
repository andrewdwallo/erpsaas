<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\Widget;
use Filament\Widgets\LineChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class DepartmentsOverview extends LineChartWidget
{
    protected function getHeading(): string
    {
        return 'Departments Overview';
    }

    protected function getData(): array
    {
        $data = Trend::model(Department::class)
        ->between(
            start: now()->startOfYear(),
            end: now()->endOfYear(),
        )
        ->perMonth()
        ->count('*');

        return [
            'datasets' => [
                [
                    'label ' => 'Departments',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
