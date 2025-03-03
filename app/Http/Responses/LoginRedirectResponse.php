<?php

namespace App\Http\Responses;

use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;
use Wallo\FilamentCompanies\FilamentCompanies;

class LoginRedirectResponse extends LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        try {
            $defaultPanelUrl = Filament::getDefaultPanel()->getUrl();
        } catch (NoDefaultPanelSetException) {
            $defaultPanelUrl = Filament::getPanel(FilamentCompanies::getCompanyPanel())->getUrl();
        }

        return redirect()->intended($defaultPanelUrl);
    }
}
