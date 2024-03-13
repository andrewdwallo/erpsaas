<?php

namespace App\Repositories\Accounting;

use App\Enums\BankAccountType;
use App\Models\Accounting\AccountSubtype;
use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountSubtypeRepository
{
    public function getDefaultAccountSubtypeByType(BankAccountType $type): string
    {
        return match ($type) {
            BankAccountType::Depository => 'Cash and Cash Equivalents',
            BankAccountType::Credit => 'Short-Term Borrowings',
            BankAccountType::Loan => 'Long-Term Borrowings',
            BankAccountType::Investment => 'Long-Term Investments',
            BankAccountType::Other => 'Other Current Assets',
        };
    }

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
