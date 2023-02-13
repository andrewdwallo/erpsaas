<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Employees extends Page
{
    protected static string $view = 'filament.pages.employees';

    protected static ?string $navigationGroup = 'Company Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeWidgets\Employees::class,
        ];
    }
}
