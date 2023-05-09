<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentCompanies\FilamentCompanies;

class Companies extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    protected static string $view = 'filament.pages.companies';

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
            Widgets\Companies\Charts\CompanyStatsOverview::class,
            Widgets\Companies\Charts\CumulativeCompanyData::class,
            Widgets\Companies\Tables\Companies::class,
        ];
    }

    protected static function getNavigationBadge(): ?string
    {
        return FilamentCompanies::companyModel()::count();
    }
}
