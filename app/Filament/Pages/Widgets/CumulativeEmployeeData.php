<?php

namespace App\Filament\Pages\Widgets;

use App\Models\Employeeship;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeEmployeeData extends ApexChartWidget
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
    protected static string $chartId = 'cumulative-employee-data';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Cumulative Employee Data';

    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $employeeData = Employeeship::selectRaw("COUNT(*) as aggregate, role, YEARWEEK(created_at, 3) as week")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('week, role')
            ->get();

        $weeks = [];
        for ($week = $startOfYear->copy(); $week->lte($today); $week->addWeek()) {
            $weeks[$week->format('oW')] = 0;
        }

        $weeklyRoleData = collect($weeks)->mapWithKeys(static function ($value, $week) use ($employeeData) {
            $editors = $employeeData->where('role', 'editor')->where('week', $week)->first();
            $admins = $employeeData->where('role', 'admin')->where('week', $week)->first();

            return [
                $week => [
                    'editors' => $editors ? $editors->aggregate : 0,
                    'admins' => $admins ? $admins->aggregate : 0,
                ]
            ];
        });

        $cumulativeEditors = $weeklyRoleData->reduce(function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value['editors'];
            return $carry;
        }, []);

        $cumulativeAdmins = $weeklyRoleData->reduce(function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value['admins'];
            return $carry;
        }, []);

        $totalEmployees = [];
        for ($i = 0; $i < count($cumulativeEditors); $i++) {
            $totalEmployees[] = $cumulativeEditors[$i] + $cumulativeAdmins[$i];
        }

        $weeklyGrowthRate = [0];
        for ($i = 1; $i < count($totalEmployees); $i++) {
            $growth = (($totalEmployees[$i] - $totalEmployees[$i - 1]) / $totalEmployees[$i - 1]) * 100;
            $weeklyGrowthRate[] = round($growth, 2);
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
                'stacked' => true,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Editors',
                    'type' => 'bar',
                    'data' => $cumulativeEditors,
                ],
                [
                    'name' => 'Admins',
                    'type' => 'bar',
                    'data' => $cumulativeAdmins,
                ],
                [
                    'name' => 'Weekly Growth Rate',
                    'type' => 'area',
                    'data' => $weeklyGrowthRate,
                ],
            ],
            'stroke' => [
                'width' => [0, 0, 2],
                'curve' => 'smooth',
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
                'labels' => [
                    'style' => [
                        'colors' => '#9ca3af',
                        'fontWeight' => 600,
                    ],
                ],
            ],
            'legend' => [
                'labels' => [
                    'colors' => '#9ca3af',
                    'fontWeight' => 600,
                ],
            ],
            'colors' => ['#d946ef', '#6d28d9', '#3b82f6'],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.2,
                    'gradientToColors' => ['#ec4899', '#8b5cf6', '#0ea5e9'],
                    'inverseColors' => true,
                    'opacityFrom' => [0.85, 0.85, 0.85],
                    'opacityTo' => [0.85, 0.85, 0.4],
                    'stops' => [0, 100, 100],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'borderRadius' => 5,
                    'borderRadiusApplication' => 'end',
                    'columnWidth' => '60%',
                    'dataLabels' => [
                        'total' => [
                            'enabled' => true,
                            'style' => [
                                'color' => '#9ca3af',
                                'fontSize' => '14px',
                                'fontWeight' => 600,
                            ],
                        ]
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }
}