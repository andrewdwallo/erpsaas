<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Companies extends Page
{
    protected static string $view = 'filament.pages.companies';

    protected static ?string $navigationGroup = 'Company Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            CompanyWidgets\Companies::class,
        ];
    }
}
