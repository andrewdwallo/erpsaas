<?php

namespace App\Services;

use App\Models\Accounting\Account;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountService
{
    public function getOrProcessAccount(BankAccount $bankAccount, Company $company, ConnectedBankAccount $connectedBankAccount): Account
    {
        if ($bankAccount->account()->doesntExist()) {
            return $this->processNewAccount($bankAccount, $company, $connectedBankAccount);
        }

        return $bankAccount->account;
    }

    public function processNewAccount(BankAccount $bankAccount, Company $company, ConnectedBankAccount $connectedBankAccount): Account
    {
        $currencyCode = $connectedBankAccount->currency_code ?? 'USD';

        $currency = $this->ensureCurrencyExists($company, $currencyCode);

        $accountSubtype = $this->getAccountSubtype($bankAccount->type->value);

        $accountSubtypeId = $this->resolveAccountSubtypeId($company, $accountSubtype);

        return $bankAccount->account()->create([
            'company_id' => $company->id,
            'name' => $connectedBankAccount->name,
            'currency_code' => $currency->code,
            'description' => $connectedBankAccount->name,
            'subtype_id' => $accountSubtypeId,
            'active' => true,
        ]);
    }

    protected function ensureCurrencyExists(Company $company, string $currencyCode): Currency
    {
        $currencyRelationship = $company->currencies();

        $defaultCurrency = $currencyRelationship->firstWhere('enabled', true);

        $hasDefaultCurrency = $defaultCurrency !== null;

        $currency_code = currency($currencyCode);

        return $currencyRelationship->firstOrCreate([
            'code' => $currencyCode,
        ], [
            'name' => $currency_code->getName(),
            'rate' => $currency_code->getRate(),
            'precision' => $currency_code->getPrecision(),
            'symbol' => $currency_code->getSymbol(),
            'symbol_first' => $currency_code->isSymbolFirst(),
            'decimal_mark' => $currency_code->getDecimalMark(),
            'thousands_separator' => $currency_code->getThousandsSeparator(),
            'enabled' => ! $hasDefaultCurrency,
        ]);
    }

    protected function getAccountSubtype(string $plaidType): string
    {
        return match ($plaidType) {
            'depository' => 'Cash and Cash Equivalents',
            'credit' => 'Short-Term Borrowings',
            'loan' => 'Long-Term Borrowings',
            'investment' => 'Long-Term Investments',
            'other' => 'Other Current Assets',
        };
    }

    protected function resolveAccountSubtypeId(Company $company, string $accountSubtype): int
    {
        $accountSubtypeId = $company->accountSubtypes()
            ->where('name', $accountSubtype)
            ->value('id');

        if ($accountSubtypeId === null) {
            throw new ModelNotFoundException("Account subtype '{$accountSubtype}' not found for company '{$company->name}'");
        }

        return $accountSubtypeId;
    }
}
