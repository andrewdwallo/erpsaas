<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Wallo\FilamentCompanies\FilamentCompanies;

class CompanyDetails extends Page
{
    public mixed $company;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Company';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Company';

    protected static string $view = 'filament.pages.company-details';

    public function mount($company): void
    {
        $this->company = FilamentCompanies::newCompanyModel()->findOrFail($company);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        Gate::authorize('view', $this->company);
    }

    public static function getSlug(): string
    {
        return '{company}/settings/company';
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true): string
    {
        return route(static::getRouteName(), ['company' => Auth::user()->currentCompany], $isAbsolute);
    }

    protected function getBreadcrumbs(): array
    {
        return [
            'company' => 'Company',
        ];
    }
}
