<?php

namespace App\Services;

use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;

class BankAccountService
{
    public function getOrProcessBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount, int | string $selectedBankAccountId): BankAccount
    {
        if ($selectedBankAccountId === 'new') {
            return $this->processNewBankAccount($company, $connectedBankAccount);
        }

        return $company->bankAccounts()->find($selectedBankAccountId);
    }

    protected function processNewBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount): BankAccount
    {
        return $connectedBankAccount->bankAccount()->create([
            'company_id' => $company->id,
            'institution_id' => $connectedBankAccount->institution_id,
            'type' => $connectedBankAccount->type,
            'number' => $connectedBankAccount->mask,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
        ]);
    }
}
