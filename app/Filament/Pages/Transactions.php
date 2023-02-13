<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Transactions extends Page
{
    protected static string $view = 'filament.pages.transactions';

    protected static ?string $navigationGroup = 'Bank';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionWidgets\Expenses::class,
            TransactionWidgets\Incomes::class,
        ];
    }
}
