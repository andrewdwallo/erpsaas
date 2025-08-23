<?php

namespace App\Filament\Company\Pages;

use Filament\Panel;
use Wallo\FilamentCompanies\Pages\Company\CompanySettings;

class ManageCompany extends CompanySettings
{
    public static function getLabel(): string
    {
        return 'Manage Company';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'manage-company';
    }
}
