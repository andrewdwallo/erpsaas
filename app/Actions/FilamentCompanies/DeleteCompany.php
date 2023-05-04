<?php

namespace App\Actions\FilamentCompanies;

use App\Models\Company;
use Wallo\FilamentCompanies\Contracts\DeletesCompanies;

class DeleteCompany implements DeletesCompanies
{
    /**
     * Delete the given company.
     */
    public function delete(Company $company): void
    {
        $company->purge();
    }
}
