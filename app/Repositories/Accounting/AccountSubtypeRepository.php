<?php

namespace App\Repositories\Accounting;

use App\Models\Accounting\AccountSubtype;
use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountSubtypeRepository
{
    public function findAccountSubtypeByNameOrFail(Company $company, $name): AccountSubtype
    {
        $accountSubtype = $company->accountSubtypes()
            ->where('name', $name)
            ->first();

        if ($accountSubtype === null) {
            throw new ModelNotFoundException("Account subtype '{$accountSubtype}' not found for company '{$company->name}'");
        }

        return $accountSubtype;
    }
}
