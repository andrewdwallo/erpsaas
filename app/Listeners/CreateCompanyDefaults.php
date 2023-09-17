<?php

namespace App\Listeners;

use App\Models\Company;
use App\Services\CompanyDefaultService;
use Wallo\FilamentCompanies\Events\CompanyCreated;

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
    public function handle(CompanyCreated $event): void
    {
        /** @var Company $company */
        $company = $event->company;

        $user = $company->owner;

        $companyDefaultService = new CompanyDefaultService();
        $companyDefaultService->createCompanyDefaults($company, $user);
    }
}
