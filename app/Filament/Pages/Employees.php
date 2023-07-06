<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentCompanies\FilamentCompanies;

class Employees extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static string $view = 'filament.pages.employees';

    protected static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->currentCompany->name === 'ERPSAAS';
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->currentCompany->name === 'ERPSAAS', 403);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\Employees\Charts\CumulativeRoles::class,
            Widgets\Employees\Charts\CumulativeGrowth::class,
            Widgets\Employees\Tables\Employees::class,
        ];
    }

    protected static function getNavigationBadge(): ?string
    {
        return FilamentCompanies::employeeshipModel()::count();
    }
}
