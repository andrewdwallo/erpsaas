<?php

namespace App\Filament\Pages\Widgets;

use App\Models\User;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeUserData extends ApexChartWidget
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
    protected static string $chartId = 'cumulative-user-data';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Cumulative User Data';

    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $userData = User::selectRaw("COUNT(*) as aggregate, YEARWEEK(created_at, 3) as week")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('week')
            ->get();

        $weeks = [];
        for ($week = $startOfYear->copy(); $week->lte($today); $week->addWeek()) {
            $weeks[$week->format('oW')] = 0;
        }

        $weeklyData = collect($weeks)->mapWithKeys(static function ($value, $week) use ($userData) {
            $matchingData = $userData->firstWhere('week', $week);
            return [$week => $matchingData ? $matchingData->aggregate : 0];
        });

        $totalUsers = $weeklyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate percentage increase and increase in companies per week
        $newUsers = [0];
        $weeklyGrowthRate = [0];

        $cumulativeDataLength = count($totalUsers);
        for ($key = 1; $key < $cumulativeDataLength; $key++) {
            $value = $totalUsers[$key];
            $previousWeekValue = $totalUsers[$key - 1];
            $newUsers[] = $value - $previousWeekValue;
            $weeklyGrowthRate[] = round((($value - $previousWeekValue) / $previousWeekValue) * 100, 2);
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
                    'data' => $weeklyGrowthRate,
                ],
                [
                    'name' => 'New Users',
                    'type' => 'line',
                    'data' => $newUsers,
                ],
                [
                    'name' => 'Total Users',
                    'type' => 'column',
                    'data' => $totalUsers,
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
                    'labels' => [
                        'style' => [
                            'colors' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                    ],
                ],
                [
                    'seriesName' => 'New Users',
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
                    'seriesName' => 'Total Users',
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
            'colors' => ['#6d28d9', '#3b82f6', '#d946ef'],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#6d28d9', '#0ea5e9', '#d946ef'],
                    'inverseColors' => false,
                    'opacityFrom' => [0.85, 1, 0.75],
                    'opacityTo' => [0.4, 0.85, 1],
                    'stops' => [0, 20, 80, 100],
                ],
            ],
            'stroke' => [
                'width' => [2, 5, 0],
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