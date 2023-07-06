<?php

namespace App\Filament\Pages\Widgets\Employees\Charts;

use App\Models\Employeeship;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeGrowth extends ApexChartWidget
{
    protected static ?int $sort = 1;

    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'cumulative-growth';

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $employeeData = Employeeship::selectRaw("COUNT(*) as aggregate, DATE_FORMAT(created_at, '%Y%m') as month")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('month')
            ->get();

        $months = [];
        for ($month = $startOfYear->copy(); $month->lte($today); $month->addMonth()) {
            $months[$month->format('Ym')] = 0;
        }

        $monthlyData = collect($months)->mapWithKeys(static function ($value, $month) use ($employeeData) {
            $matchingData = $employeeData->firstWhere('month', $month);
            return [$month => $matchingData->aggregate ?? 0];
        });

        $totalEmployees = $monthlyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate percentage increase and increase in companies per month
        $newEmployees = [0];
        $monthlyPercentageChange = [0];

        for ($i = 1, $iMax = count($totalEmployees); $i < $iMax; $i++) {
            $newEmployees[] = $totalEmployees[$i] - $totalEmployees[$i - 1];
            $monthlyPercentageChange[] = ($newEmployees[$i] / $totalEmployees[$i - 1]) * 100;
        }

        $labels = collect($months)->keys()->map(static function ($month) {
            $year = substr($month, 0, 4);
            $monthNumber = substr($month, 4);

            return today()->startOfYear()->setDate($year, $monthNumber, 1)->format('M');
        });


        return [
            'chart' => [
                'type' => 'area',
                'height' => 350,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'title' => [
                'text' => 'Cumulative Growth',
                'align' => 'left',
                'margin' => 20,
                'style' => [
                    'fontSize' => '20px',
                ],
            ],
            'subtitle' => [
                'text' => 'Monthly',
                'align' => 'left',
                'margin' => 20,
                'style' => [
                    'fontSize' => '14px',
                ],
            ],
            'series' => [
                [
                    'name' => 'Growth Rate',
                    'data' => $monthlyPercentageChange,
                ],
                [
                    'name' => 'New Employees',
                    'data' => $newEmployees,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'position' => 'bottom',
                'labels' => [
                    'show' => true,
                    'style' => [
                        'colors' => '#9ca3af',
                    ],
                ],
            ],
            'yaxis' => [
                'decimalsInFloat' => 2,
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => true,
                'position' => 'bottom',
                'horizontalAlign' => 'center',
                'floating' => false,
                'labels' => [
                    'useSeriesColors' => true,
                ],
                'markers' => [
                    'width' => 30,
                    'height' => 8,
                    'radius' => 0,
                ],
            ],
            'colors' => ['#454DC8', '#22d3ee'],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'opacityFrom' => 0.6,
                    'opacityTo' => 0.8,
                ],
            ],
            'markers' => [
                'size' => 4,
                'hover' => [
                    'size' => 7,
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
            ],
        ];
    }
}
