<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Repositories\Accounting\AccountSubtypeRepository;
use App\Repositories\Banking\ConnectedBankAccountRepository;

class ConnectedBankAccountService
{
    public function __construct(
        protected AccountSubtypeRepository $accountSubtypeRepository,
        protected ConnectedBankAccountRepository $connectedBankAccountRepository
    ) {}

    public function getOrProcessBankAccountForConnectedBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount, int | string $selectedBankAccountId): BankAccount
    {
        if ($selectedBankAccountId === 'new') {
            return $this->connectedBankAccountRepository->createBankAccountForConnectedBankAccount($company, $connectedBankAccount);
        }

        return $company->bankAccounts()->find($selectedBankAccountId);
    }

    public function getOrProcessAccountForConnectedBankAccount(BankAccount $bankAccount, Company $company, ConnectedBankAccount $connectedBankAccount): Account
    {
        if ($bankAccount->account()->doesntExist()) {
            return $this->processNewAccountForBank($bankAccount, $company, $connectedBankAccount);
        }

        return $bankAccount->account;
    }

    public function processNewAccountForBank(BankAccount $bankAccount, Company $company, ConnectedBankAccount $connectedBankAccount): Account
    {
        $defaultAccountSubtypeName = $bankAccount->type->getDefaultSubtype();

        $accountSubtype = $this->accountSubtypeRepository->findAccountSubtypeByNameOrFail($company, $defaultAccountSubtypeName);

        return $this->connectedBankAccountRepository->createAccountForConnectedBankAccount($company, $connectedBankAccount, $bankAccount, $accountSubtype);
    }
}
