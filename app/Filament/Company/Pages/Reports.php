<?php

namespace App\Filament\Company\Pages;

use App\Filament\Company\Pages\Reports\AccountBalances;
use App\Infolists\Components\ReportEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.company.pages.reports';

    public function reportsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([])
            ->schema([
                Section::make('Detailed Reports')
                    ->aside()
                    ->description('Dig into the details of your business’s transactions, balances, and accounts.')
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('account_balances')
                            ->hiddenLabel()
                            ->heading('Account Balances')
                            ->description('Summary view of balances and activity for all accounts.')
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor(Color::Teal)
                            ->url(AccountBalances::getUrl()),
                        ReportEntry::make('trial_balance')
                            ->hiddenLabel()
                            ->heading('Trial Balance')
                            ->description('The sum of all debit and credit balances for all accounts on a single day. This helps to ensure that the books are in balance.')
                            ->icon('heroicon-o-scale')
                            ->iconColor(Color::Sky)
                            ->url('#'),
                        ReportEntry::make('account_transactions')
                            ->hiddenLabel()
                            ->heading('Account Transactions')
                            ->description('A record of all transactions for a company. The general ledger is the core of a company\'s financial records.')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->iconColor(Color::Amber)
                            ->url('#'),
                    ]),
            ]);
    }
}
