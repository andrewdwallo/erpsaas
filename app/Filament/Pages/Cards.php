<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Cards extends Page
{
    protected static string $view = 'filament.pages.cards';

    protected static ?string $navigationGroup = 'Bank';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            CardWidgets\Cards::class,
        ];
    }
}
