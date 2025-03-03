<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\Widgets;

use App\Enums\Accounting\BillStatus;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class BillOverview extends EnhancedStatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $unpaidBills = $this->record->bills()
            ->whereIn('status', [BillStatus::Open, BillStatus::Partial, BillStatus::Overdue]);

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

        $averagePaymentTime = $this->record->bills()
            ->whereNotNull('paid_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, date, paid_at)) as avg_days')
            ->value('avg_days');

        $averagePaymentTimeFormatted = Number::format($averagePaymentTime ?? 0, maxPrecision: 1);

        $lastMonthTotal = $this->record->bills()
            ->where('status', BillStatus::Paid)
            ->whereBetween('date', [
                today()->subMonth()->startOfMonth(),
                today()->subMonth()->endOfMonth(),
            ])
            ->get()
            ->sumMoneyInDefaultCurrency('amount_paid');

        return [
            EnhancedStatsOverviewWidget\EnhancedStat::make('Total To Pay', CurrencyConverter::formatCentsToMoney($amountToPay))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description('Includes ' . CurrencyConverter::formatCentsToMoney($amountOverdue) . ' overdue'),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Due Within 7 Days', CurrencyConverter::formatCentsToMoney($amountDueWithin7Days))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Average Payment Time', $averagePaymentTimeFormatted)
                ->suffix('days'),

            EnhancedStatsOverviewWidget\EnhancedStat::make('Paid Last Month', CurrencyConverter::formatCentsToMoney($lastMonthTotal))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
        ];
    }
}
