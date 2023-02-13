<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Accounts extends Page
{
    protected static string $view = 'filament.pages.accounts';

    protected static ?string $navigationGroup = 'Bank';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidgets\Accounts::class,
        ];
    }
}
