<?php

namespace App\Filament\Pages\Widgets\Companies\Charts;

use App\Models\Company;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeCompanyData extends ApexChartWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'cumulative-company-data';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Cumulative Company Data';

    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $companyData = Company::selectRaw("COUNT(*) as aggregate, YEARWEEK(created_at, 3) as week")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('week')
            ->get();

        $weeks = [];
        for ($week = $startOfYear->copy(); $week->lte($today); $week->addWeek()) {
            $weeks[$week->format('oW')] = 0;
        }

        $weeklyData = collect($weeks)->mapWithKeys(static function ($value, $week) use ($companyData) {
            $matchingData = $companyData->firstWhere('week', $week);
            return [$week => $matchingData ? $matchingData->aggregate : 0];
        });

        $totalCompanies = $weeklyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate percentage increase and increase in companies per week
        $newCompanies = [0];
        $weeklyPercentageChange = [0];

        for ($i = 1; $i < count($totalCompanies); $i++) {
            $newCompanies[] = $totalCompanies[$i] - $totalCompanies[$i - 1];
            $weeklyPercentageChange[] = ($newCompanies[$i] / $totalCompanies[$i - 1]) * 100;
        }

        // Calculate exponential smoothing for total companies
        $alpha = 0.3; // Smoothing factor, between 0 and 1
        $smoothedTotalCompanies = [];

        $smoothedTotalCompanies[0] = $totalCompanies[0]; // Initialize the first smoothed value
        for ($i = 1; $i < count($totalCompanies); $i++) {
            $smoothedTotalCompanies[$i] = $alpha * $totalCompanies[$i] + (1 - $alpha) * $smoothedTotalCompanies[$i - 1];
        }

        $labels = collect($weeks)->keys()->map(static function ($week) {
            $year = substr($week, 0, 4);
            $weekNumber = substr($week, 4);

            return today()->setISODate($year, $weekNumber)->format('M d');
        });

        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
                'stacked' => false,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Weekly Growth Rate',
                    'type' => 'area',
                    'data' => $weeklyPercentageChange,
                ],
                [
                    'name' => 'New Companies',
                    'type' => 'line',
                    'data' => $newCompanies,
                ],
                [
                    'name' => 'Smoothed Total Companies',
                    'type' => 'line',
                    'data' => $smoothedTotalCompanies,
                ],
                [
                    'name' => 'Total Companies',
                    'type' => 'column',
                    'data' => $totalCompanies,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'position' => 'bottom',
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'yaxis' => [
                [
                    'seriesName' => 'Weekly Growth Rate',
                    'decimalsInFloat' => 2,
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                    ],
                ],
                [
                    'seriesName' => 'New Companies',
                    'decimalsInFloat' => 0,
                    'opposite' => true,
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                    ],
                ],
                [
                    'seriesName' => 'Smoothed Total Companies',
                    'decimalsInFloat' => 0,
                    'opposite' => true,
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                    ],
                ],
                [
                    'seriesName' => 'Total Companies',
                    'decimalsInFloat' => 0,
                    'opposite' => true,
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                    ],
                ],
            ],
            'legend' => [
                'position' => 'top',
                'horizontalAlign' => 'center',
                'labels' => [
                    'colors' => '#9ca3af',
                    'fontWeight' => 600,
                ],
            ],
            'markers' => [
                'size' => 0,
            ],
            'colors' => ['#d946ef', '#6d28d9', '#14b8a6', '#3b82f6'],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#d946ef', '#6d28d9', '#14b8a6', '#0ea5e9'],
                    'inverseColors' => false,
                    'opacityFrom' => [0.85, 1, 1, 0.75],
                    'opacityTo' => [0.4, 0.85, 0.85, 1],
                    'stops' => [0, 20, 80, 100],
                ],
            ],
            'stroke' => [
                'width' => [2, 5, 5, 0],
                'curve' => 'smooth',
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 5,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '60%',
                ],
            ],
        ];
    }
}