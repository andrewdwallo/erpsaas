<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Widgets;

use App\Enums\Accounting\BillStatus;
use App\Filament\Company\Resources\Purchases\BillResource\Pages\ListBills;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Filament\Widgets\EnhancedStatsOverviewWidget\EnhancedStat;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class BillOverview extends EnhancedStatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListBills::class;
    }

    protected function getStats(): array
    {
        $activeTab = $this->activeTab;

        $averagePaymentTimeFormatted = '-';
        $averagePaymentTimeSuffix = null;
        $lastMonthTotal = '-';
        $lastMonthTotalSuffix = null;

        if ($activeTab !== 'unpaid') {
            $driver = DB::getDriverName();

            $query = $this->getPageTableQuery()
                ->whereNotNull('paid_at');

            if ($driver === 'pgsql') {
                $query->selectRaw('AVG(EXTRACT(EPOCH FROM (paid_at - date)) / 86400) as avg_days');
            } else {
                $query->selectRaw('AVG(TIMESTAMPDIFF(DAY, date, paid_at)) as avg_days');
            }

            $averagePaymentTime = $query
                ->groupBy('company_id')
                ->reorder()
                ->value('avg_days');

            $averagePaymentTimeFormatted = Number::format($averagePaymentTime ?? 0, maxPrecision: 1);
            $averagePaymentTimeSuffix = 'days';

            $lastMonthPaid = $this->getPageTableQuery()
                ->whereBetween('date', [
                    today()->subMonth()->startOfMonth(),
                    today()->subMonth()->endOfMonth(),
                ])
                ->get()
                ->sumMoneyInDefaultCurrency('amount_paid');

            $lastMonthTotal = CurrencyConverter::formatCentsToMoney($lastMonthPaid);
            $lastMonthTotalSuffix = CurrencyAccessor::getDefaultCurrency();
        }

        if ($activeTab === 'paid') {
            return [
                EnhancedStat::make('Total To Pay', '-'),
                EnhancedStat::make('Due Within 7 Days', '-'),
                EnhancedStat::make('Average Payment Time', $averagePaymentTimeFormatted)
                    ->suffix($averagePaymentTimeSuffix),
                EnhancedStat::make('Paid Last Month', $lastMonthTotal)
                    ->suffix($lastMonthTotalSuffix),
            ];
        }

        $unpaidBills = $this->getPageTableQuery()
            ->unpaid();

        $amountToPay = $unpaidBills->get()->sumMoneyInDefaultCurrency('amount_due');

        $amountOverdue = $unpaidBills
            ->clone()
            ->where('status', BillStatus::Overdue)
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        $amountDueWithin7Days = $unpaidBills
            ->clone()
            ->whereBetween('due_date', [today(), today()->addWeek()])
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        return [
            EnhancedStat::make('Total To Pay', CurrencyConverter::formatCentsToMoney($amountToPay))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description('Includes ' . CurrencyConverter::formatCentsToMoney($amountOverdue) . ' overdue'),
            EnhancedStat::make('Due Within 7 Days', CurrencyConverter::formatCentsToMoney($amountDueWithin7Days))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
            EnhancedStat::make('Average Payment Time', $averagePaymentTimeFormatted)
                ->suffix($averagePaymentTimeSuffix),
            EnhancedStat::make('Paid Last Month', $lastMonthTotal)
                ->suffix($lastMonthTotalSuffix),
        ];
    }
}
