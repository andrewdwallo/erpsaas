<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Widgets;

use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ListInvoices;
use App\Filament\Widgets\EnhancedStatsOverviewWidget;
use App\Filament\Widgets\EnhancedStatsOverviewWidget\EnhancedStat;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;
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
        $activeTab = $this->activeTab;

        if ($activeTab === 'draft') {
            $draftInvoices = $this->getPageTableQuery();
            $totalDraftCount = $draftInvoices->count();
            $totalDraftAmount = $draftInvoices->get()->sumMoneyInDefaultCurrency('total');

            $averageDraftTotal = $totalDraftCount > 0
                ? (int) round($totalDraftAmount / $totalDraftCount)
                : 0;

            return [
                EnhancedStat::make('Total Unpaid', '-'),
                EnhancedStat::make('Due Within 30 Days', '-'),
                EnhancedStat::make('Average Payment Time', '-'),
                EnhancedStat::make('Average Invoice Total', CurrencyConverter::formatCentsToMoney($averageDraftTotal))
                    ->suffix(CurrencyAccessor::getDefaultCurrency()),
            ];
        }

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

        $averagePaymentTimeFormatted = '-';
        $averagePaymentTimeSuffix = null;

        if ($activeTab !== 'unpaid') {
            $driver = DB::getDriverName();

            $query = $this->getPageTableQuery()
                ->whereNotNull('paid_at');

            if ($driver === 'pgsql') {
                $query->selectRaw('AVG(EXTRACT(EPOCH FROM (paid_at - approved_at)) / 86400) as avg_days');
            } else {
                $query->selectRaw('AVG(TIMESTAMPDIFF(DAY, approved_at, paid_at)) as avg_days');
            }

            $averagePaymentTime = $query
                ->groupBy('company_id')
                ->reorder()
                ->value('avg_days');

            $averagePaymentTimeFormatted = Number::format($averagePaymentTime ?? 0, maxPrecision: 1);
            $averagePaymentTimeSuffix = 'days';
        }

        return [
            EnhancedStat::make('Total Unpaid', CurrencyConverter::formatCentsToMoney($amountUnpaid))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description('Includes ' . CurrencyConverter::formatCentsToMoney($amountOverdue) . ' overdue'),
            EnhancedStat::make('Due Within 30 Days', CurrencyConverter::formatCentsToMoney($amountDueWithin30Days))
                ->suffix(CurrencyAccessor::getDefaultCurrency()),
            EnhancedStat::make('Average Payment Time', $averagePaymentTimeFormatted)
                ->suffix($averagePaymentTimeSuffix),
            EnhancedStat::make('Average Invoice Total', CurrencyConverter::formatCentsToMoney($averageInvoiceTotal))
                ->suffix(CurrencyAccessor::getDefaultCurrency())
                ->description($activeTab === 'all' ? 'Excludes draft and voided invoices' : null),
        ];
    }
}
