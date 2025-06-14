<?php

namespace App\Filament\Company\Resources\Sales\EstimateResource\Widgets;

use App\Enums\Accounting\EstimateStatus;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\ListEstimates;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Filament\Widgets\EnhancedStatsOverviewWidget\EnhancedStat;
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
        $activeTab = $this->activeTab;

        if ($activeTab === 'draft') {
            $draftEstimates = $this->getPageTableQuery();
            $totalDraftCount = $draftEstimates->count();
            $totalDraftAmount = $draftEstimates->get()->sumMoneyInDefaultCurrency('total');

            $averageDraftTotal = $totalDraftCount > 0
                ? (int) round($totalDraftAmount / $totalDraftCount)
                : 0;

            return [
                EnhancedStat::make('Active Estimates', '-'),
                EnhancedStat::make('Accepted Estimates', '-'),
                EnhancedStat::make('Converted Estimates', '-'),
                EnhancedStat::make('Average Estimate Total', CurrencyConverter::formatCentsToMoney($averageDraftTotal))
                    ->suffix(CurrencyAccessor::getDefaultCurrency()),
            ];
        }

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

        $validEstimates = $this->getPageTableQuery()
            ->whereNotIn('status', [
                EstimateStatus::Draft,
            ]);

        $totalValidEstimatesCount = $validEstimates->count();
        $totalValidEstimateAmount = $validEstimates->get()->sumMoneyInDefaultCurrency('total');

        $averageEstimateTotal = $totalValidEstimatesCount > 0
            ? (int) round($totalValidEstimateAmount / $totalValidEstimatesCount)
            : 0;

        $percentConverted = '-';
        $percentConvertedSuffix = null;
        $percentConvertedDescription = null;

        if ($activeTab !== 'active') {
            $percentConverted = $totalValidEstimatesCount > 0
                ? Number::percentage(($totalConvertedCount / $totalValidEstimatesCount) * 100, maxPrecision: 1)
                : Number::percentage(0, maxPrecision: 1);

            $percentConvertedSuffix = 'converted';
            $percentConvertedDescription = $totalConvertedCount . ' converted';
        }

        return [
            EnhancedStat::make('Active Estimates', CurrencyConverter::formatCentsToMoney($totalActiveAmount))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($totalActiveCount . ' active estimates'),

            EnhancedStat::make('Accepted Estimates', CurrencyConverter::formatCentsToMoney($totalAcceptedAmount))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($totalAcceptedCount . ' accepted'),

            EnhancedStat::make('Converted Estimates', $percentConverted)
                ->suffix($percentConvertedSuffix)
                ->description($percentConvertedDescription),

            EnhancedStat::make('Average Estimate Total', CurrencyConverter::formatCentsToMoney($averageEstimateTotal))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($activeTab === 'all' ? 'Excludes draft estimates' : null),
        ];
    }
}
