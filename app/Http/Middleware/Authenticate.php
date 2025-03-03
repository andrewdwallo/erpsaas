<?php

namespace App\Http\Middleware;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as Middleware;
use Wallo\FilamentCompanies\FilamentCompanies;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        try {
            $defaultPanelLoginUrl = Filament::getDefaultPanel()->getLoginUrl();
        } catch (NoDefaultPanelSetException) {
            $defaultPanelLoginUrl = Filament::getPanel(FilamentCompanies::getCompanyPanel())->getLoginUrl();
        }

        return $defaultPanelLoginUrl;
    }
}
