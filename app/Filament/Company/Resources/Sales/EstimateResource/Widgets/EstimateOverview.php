<?php

namespace App\Filament\Company\Resources\Sales\EstimateResource\Widgets;

use App\Enums\Accounting\EstimateStatus;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\ListEstimates;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Number;

class EstimateOverview extends EnhancedStatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListEstimates::class;
    }

    protected function getStats(): array
    {
        $activeEstimates = $this->getPageTableQuery()->active();

        $totalActiveCount = $activeEstimates->count();
        $totalActiveAmount = $activeEstimates->get()->sumMoneyInDefaultCurrency('total');

        $acceptedEstimates = $this->getPageTableQuery()
            ->where('status', EstimateStatus::Accepted);

        $totalAcceptedCount = $acceptedEstimates->count();
        $totalAcceptedAmount = $acceptedEstimates->get()->sumMoneyInDefaultCurrency('total');

        $convertedEstimates = $this->getPageTableQuery()
            ->where('status', EstimateStatus::Converted);

        $totalConvertedCount = $convertedEstimates->count();
        $totalEstimatesCount = $this->getPageTableQuery()->count();

        $percentConverted = $totalEstimatesCount > 0
            ? Number::percentage(($totalConvertedCount / $totalEstimatesCount) * 100, maxPrecision: 1)
            : Number::percentage(0, maxPrecision: 1);

        $totalEstimateAmount = $this->getPageTableQuery()
            ->get()
            ->sumMoneyInDefaultCurrency('total');

        $averageEstimateTotal = $totalEstimatesCount > 0
            ? (int) round($totalEstimateAmount / $totalEstimatesCount)
            : 0;

        return [
            EnhancedStatsOverviewWidget\EnhancedStat::make('Active Estimates', CurrencyConverter::formatCentsToMoney($totalActiveAmount))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($totalActiveCount . ' active estimates'),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Accepted Estimates', CurrencyConverter::formatCentsToMoney($totalAcceptedAmount))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($totalAcceptedCount . ' accepted'),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Converted Estimates', $percentConverted)
                ->suffix('converted')
                ->description($totalConvertedCount . ' converted'),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Average Estimate Total', CurrencyConverter::formatCentsToMoney($averageEstimateTotal))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
        ];
    }
}
