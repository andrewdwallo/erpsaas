<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Pages\Widgets;

class ChartOfAccounts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.chart-of-accounts';

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\Assets::class,
            Widgets\Liabilities::class,
            Widgets\Expenses::class,
            Widgets\Revenues::class,
            Widgets\Equities::class,
        ];
    }


}
