<?php

namespace App\Listeners;

use App\Events\CompanyGenerated;
use App\Services\CompanyDefaultService;

class CreateCompanyDefaults
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyGenerated $event): void
    {
        $company = $event->company;
        $countryCode = $event->country;
        $languageCode = $event->language;
        $currency = $event->currency;

        $user = $company->owner;

        $companyDefaultService = new CompanyDefaultService();
        $companyDefaultService->createCompanyDefaults($company, $user, $currency, $countryCode, $languageCode);
    }
}
