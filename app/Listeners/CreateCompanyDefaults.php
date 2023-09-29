<?php

namespace App\Listeners;

use App\Events\CompanyGenerated;
use App\Models\Locale\Country;
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

        $currencyCode = Country::where('iso_code_2', $countryCode)->pluck('currency_code')->first();

        $user = $company->owner;

        $companyDefaultService = new CompanyDefaultService();
        $companyDefaultService->createCompanyDefaults($company, $user, $currencyCode);
    }
}
