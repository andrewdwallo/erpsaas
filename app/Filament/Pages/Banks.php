<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Banks extends Page
{
    protected static string $view = 'filament.pages.banks';

    protected static ?string $navigationGroup = 'Bank';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            BankWidgets\Banks::class,
        ];
    }
}
