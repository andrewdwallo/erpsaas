<?php

namespace App\Filament\Pages\Widgets\Companies\Charts;

use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget;

class CompanyStatsOverview extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 3;

    protected function getColumns(): int
    {
        return 3;
    }

    /**
     * Holt's Linear Trend
     */
    protected function holtLinearTrend($data, $alpha, $beta): array
    {
        $level = $data[0];
        $trend = $data[1] - $data[0];

        $forecast = [];
        for ($i = 0; $i < count($data); $i++) {
            $prev_level = $level;
            $level = $alpha * $data[$i] + (1 - $alpha) * ($prev_level + $trend);
            $trend = $beta * ($level - $prev_level) + (1 - $beta) * $trend;
            $forecast[] = $level + $trend;
        }

        return $forecast;
    }

    /**
     * Chart Options
     */
    protected function getCards(): array
    {
        // Define constants
        $alpha = 0.8;
        $beta = 0.2;

        // Define time variables
        $startOfYear = today()->startOfYear();
        $today = today();

        // Get Company Data
        $companyData = Company::selectRaw("COUNT(*) as aggregate, YEARWEEK(created_at, 3) as week")
            ->whereBetween('created_at', [$startOfYear, $today])
            ->groupByRaw('week')
            ->get();

        // Initialize weeks
        $weeks = [];
        for ($week = $startOfYear->copy(); $week->lte($today); $week->addWeek()) {
            $weeks[$week->format('oW')] = 0;
        }

        // Get Weekly Data
        $weeklyData = collect($weeks)->mapWithKeys(static function ($value, $week) use ($companyData) {
            $matchingData = $companyData->firstWhere('week', $week);
            return [$week => $matchingData ? $matchingData->aggregate : 0];
        });

        // Calculate total companies
        $totalCompanies = $weeklyData->reduce(static function ($carry, $value) {
            $carry[] = ($carry ? end($carry) : 0) + $value;
            return $carry;
        }, []);

        // Calculate new companies and percentage change
        $newCompanies = [0];
        $weeklyPercentageChange = [0];
        for ($i = 1; $i < count($totalCompanies); $i++) {
            $newCompanies[] = $totalCompanies[$i] - $totalCompanies[$i - 1];
            $weeklyPercentageChange[] = ($newCompanies[$i] / $totalCompanies[$i - 1]) * 100;
        }

        // Calculate average weekly growth rate
        $totalWeeks = $startOfYear->diffInWeeks($today);
        $averageWeeklyGrowthRate = round(array_sum($weeklyPercentageChange) / ($totalWeeks), 2);

        // Calculate Holt's forecast
        $weeklyDataArray = $weeklyData->values()->toArray();
        $holt_forecast = $this->holtLinearTrend($weeklyDataArray, $alpha, $beta);
        $expectedNewCompanies = round(end($holt_forecast));

        // Prepare cards for return
        return [
            StatsOverviewWidget\Card::make("New Companies Forecast (Holt's Trend)", $expectedNewCompanies),
            StatsOverviewWidget\Card::make('Average Weekly Growth Rate', $averageWeeklyGrowthRate . '%'),
            StatsOverviewWidget\Card::make('Personal Companies', Company::sum('personal_company')),
        ];
    }
}