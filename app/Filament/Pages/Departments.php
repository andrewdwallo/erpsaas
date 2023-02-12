<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Departments extends Page
{
    protected static string $view = 'filament.pages.departments';

    protected static ?string $navigationGroup = 'Company Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            DepartmentWidgets\Departments::class,
        ];
    }
}
