<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ChartOfAccounts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.chart-of-accounts';

    protected function getHeaderWidgets(): array
    {
        return [
            ChartOfAccountsWidgets\Assets::class,
            ChartOfAccountsWidgets\Liabilities::class,
            ChartOfAccountsWidgets\Expenses::class,
            ChartOfAccountsWidgets\Revenues::class,
            ChartOfAccountsWidgets\Equities::class,
        ];
    }
}
