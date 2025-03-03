<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\Widgets;

use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

class InvoiceOverview extends EnhancedStatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $unpaidInvoices = $this->record->invoices()->unpaid();

        $amountUnpaid = $unpaidInvoices->get()->sumMoneyInDefaultCurrency('amount_due');

        $amountOverdue = $unpaidInvoices->clone()
            ->where('status', InvoiceStatus::Overdue)
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        $amountDueWithin30Days = $unpaidInvoices->clone()
            ->whereBetween('due_date', [today(), today()->addMonth()])
            ->get()
            ->sumMoneyInDefaultCurrency('amount_due');

        $validInvoices = $this->record->invoices()
            ->whereNotIn('status', [
                InvoiceStatus::Void,
                InvoiceStatus::Draft,
            ]);

        $totalValidInvoiceAmount = $validInvoices->get()->sumMoneyInDefaultCurrency('total');

        $totalValidInvoiceCount = $validInvoices->count();

        $averageInvoiceTotal = $totalValidInvoiceCount > 0
            ? (int) round($totalValidInvoiceAmount / $totalValidInvoiceCount)
            : 0;

        $averagePaymentTime = $this->record->invoices()
            ->whereNotNull('paid_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, date, paid_at)) as avg_days')
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
