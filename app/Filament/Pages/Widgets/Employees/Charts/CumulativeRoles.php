<?php

namespace App\Filament\Pages\Widgets\Employees\Charts;

use App\Models\Employeeship;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeRoles extends ApexChartWidget
{
    protected static ?int $sort = 0;

    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'cumulative-roles';

    protected static ?string $pollingInterval = null;

    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $employeeData = Employeeship::selectRaw("COUNT(*) as aggregate, role, DATE_FORMAT(created_at, '%Y%m') as month")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('month, role')
            ->get();

        $months = [];
        for ($month = $startOfYear->copy(); $month->lte($today); $month->addMonth()) {
            $months[$month->format('Ym')] = 0;
        }

        $monthlyRoleData = collect($months)->mapWithKeys(static function ($value, $month) use ($employeeData) {
            $editors = $employeeData->where('role', 'editor')->where('month', $month)->first();
            $admins = $employeeData->where('role', 'admin')->where('month', $month)->first();

            return [
                $month => [
                    'editors' => $editors->aggregate ?? 0,
                    'admins' => $admins->aggregate ?? 0,
                ]
            ];
        });

        $cumulativeEditors = $monthlyRoleData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value['editors'];
            return $carry;
        }, []);

        $cumulativeAdmins = $monthlyRoleData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value['admins'];
            return $carry;
        }, []);

        $labels = collect($months)->keys()->map(static function ($month) {
            $year = substr($month, 0, 4);
            $monthNumber = substr($month, 4);

            return today()->startOfYear()->setDate($year, $monthNumber, 1)->format('M');
        });

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'title' => [
                'text' => 'Cumulative Roles',
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
                    'name' => 'Editors',
                    'data' => $cumulativeEditors,
                ],
                [
                    'name' => 'Admins',
                    'data' => $cumulativeAdmins,
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
                    'width' => 12,
                    'height' => 12,
                    'radius' => 0,
                ],
            ],
            'tooltip' => [
                'enabled' => true,
                'shared' => true,
                'intersect' => false,
                'x' => [
                    'show' => true,
                ],
            ],
            'colors' => ['#454DC8', '#22d3ee'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'endingShape' => 'rounded',
                    'columnWidth' => '55%',
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
