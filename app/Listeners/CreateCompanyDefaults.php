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
        $country = $event->country;

        $currencyCode = $country ? country($country)->getCurrency()['iso_4217_code'] : 'USD';

        $user = $company->owner;

        $companyDefaultService = new CompanyDefaultService();
        $companyDefaultService->createCompanyDefaults($company, $user, $currencyCode);
    }
}
