<?php

namespace App\Filament\Pages\Widgets\Companies\Charts;

use App\Models\Company;
use Illuminate\Contracts\View\View;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CumulativeTotal extends ApexChartWidget
{
    protected static ?int $sort = 2;

    /**
     * Chart Id
     *
     * @var string
     */
    protected static string $chartId = 'cumulative-total';

    protected static ?string $pollingInterval = null;


    protected function getOptions(): array
    {
        $startOfYear = today()->startOfYear();
        $today = today();

        // Company data
        $companyData = Company::selectRaw("COUNT(*) as aggregate, DATE_FORMAT(created_at, '%Y%m') as month")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('month')
            ->get();

        $months = [];
        for ($month = $startOfYear->copy(); $month->lte($today); $month->addMonth()) {
            $months[$month->format('Ym')] = 0;
        }

        $monthlyData = collect($months)->mapWithKeys(static function ($value, $month) use ($companyData) {
            $matchingData = $companyData->firstWhere('month', $month);
            return [$month => $matchingData->aggregate ?? 0];
        });

        $totalCompanies = $monthlyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate exponential smoothing for total companies
        $alpha = 0.3; // Smoothing factor, between 0 and 1
        $smoothedTotalCompanies = [];

        $smoothedTotalCompanies[0] = $totalCompanies[0]; // Initialize the first smoothed value
        for ($i = 1, $iMax = count($totalCompanies); $i < $iMax; $i++) {
            $smoothedTotalCompanies[$i] = $alpha * $totalCompanies[$i] + (1 - $alpha) * $smoothedTotalCompanies[$i - 1];
        }

        $labels = collect($months)->keys()->map(static function ($month) {
            $year = substr($month, 0, 4);
            $monthNumber = substr($month, 4);

            return today()->startOfYear()->setDate($year, $monthNumber, 1)->format('M');
        });

        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
                'fontFamily' => 'inherit',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'title' => [
                'text' => 'Cumulative Total',
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
                    'name' => 'Total Companies',
                    'data' => $totalCompanies,
                ],
                [
                    'name' => 'Smoothed Total Companies',
                    'data' => $smoothedTotalCompanies,
                ],
            ],
            'xaxis' => [
                'type' => 'category',
                'categories' => $labels,
                'position' => 'bottom',
                'labels' => [
                    'show' => true,
                ],
            ],
            'yaxis' => [
                'decimalsInFloat' => 0,
                'labels' => [
                    'show' => true,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => true,
                'position' => 'bottom', // Placing the legend at the right side of the chart.
                'horizontalAlign' => 'center', // Centering the legend items horizontally.
                'floating' => false,
                'labels' => [
                    'useSeriesColors' => true,
                ],
                'markers' => [
                    'width' => 30,
                    'height' => 4,
                    'radius' => 4,
                ],
            ],
            'colors' => ['#454DC8', '#22d3ee'],
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