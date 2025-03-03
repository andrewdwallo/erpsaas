<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Widgets;

use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ListInvoices;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Number;

class InvoiceOverview extends EnhancedStatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListInvoices::class;
    }

    protected function getStats(): array
    {
        $unpaidInvoices = $this->getPageTableQuery()->unpaid();

        $amountUnpaid = $unpaidInvoices->get()->sumMoneyInDefaultCurrency('amount_due');

        $amountOverdue = $unpaidInvoices
            ->clone()
            ->where('status', InvoiceStatus::Overdue)
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        $amountDueWithin30Days = $unpaidInvoices
            ->clone()
            ->whereBetween('due_date', [today(), today()->addMonth()])
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        $validInvoices = $this->getPageTableQuery()
            ->whereNotIn('status', [
                InvoiceStatus::Void,
                InvoiceStatus::Draft,
            ]);

        $totalValidInvoiceAmount = $validInvoices->get()->sumMoneyInDefaultCurrency('total');

        $totalValidInvoiceCount = $validInvoices->count();

        $averageInvoiceTotal = $totalValidInvoiceCount > 0
            ? (int) round($totalValidInvoiceAmount / $totalValidInvoiceCount)
            : 0;

        $averagePaymentTime = $this->getPageTableQuery()
            ->whereNotNull('paid_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, approved_at, paid_at)) as avg_days')
            ->value('avg_days');

        $averagePaymentTimeFormatted = Number::format($averagePaymentTime ?? 0, maxPrecision: 1);

        return [
            EnhancedStatsOverviewWidget\EnhancedStat::make('Total Unpaid', CurrencyConverter::formatCentsToMoney($amountUnpaid))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description('Includes ' . CurrencyConverter::formatCentsToMoney($amountOverdue) . ' overdue'),
            EnhancedStatsOverviewWidget\EnhancedStat::make('Due Within 30 Days', CurrencyConverter::formatCentsToMoney($amountDueWithin30Days))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
            EnhancedStatsOverviewWidget\EnhancedStat::make('Average Payment Time', $averagePaymentTimeFormatted)
                ->suffix('days'),
            EnhancedStatsOverviewWidget\EnhancedStat::make('Average Invoice Total', CurrencyConverter::formatCentsToMoney($averageInvoiceTotal))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
        ];
    }
}
