<?php

namespace App\Repositories\Banking;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;

class ConnectedBankAccountRepository
{
    public function createBankAccountForConnectedBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount)
    {
        return $connectedBankAccount->bankAccount()->create([
            'company_id' => $company->id,
            'institution_id' => $connectedBankAccount->institution_id,
            'type' => $connectedBankAccount->type,
            'number' => $connectedBankAccount->mask,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
        ]);
    }

    public function createAccountForConnectedBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount, BankAccount $bankAccount, AccountSubtype $accountSubtype): Account
    {
        return $bankAccount->account()->create([
            'company_id' => $company->id,
            'name' => $connectedBankAccount->name,
            'currency_code' => $connectedBankAccount->currency_code,
            'description' => $connectedBankAccount->name,
            'subtype_id' => $accountSubtype->id,
            'active' => true,
        ]);
    }
}
