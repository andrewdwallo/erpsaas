<?php

namespace App\Listeners;

use App\Events\StartTransactionImport;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Models\Setting\Currency;
use App\Services\PlaidService;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class HandleTransactionImport
{
    protected PlaidService $plaid;

    /**
     * Create the event listener.
     */
    public function __construct(PlaidService $plaid)
    {
        $this->plaid = $plaid;
    }

    /**
     * Handle the event.
     */
    public function handle(StartTransactionImport $event): void
    {
        DB::transaction(function () use ($event) {
            $this->processTransactionImport($event);
        });
    }

    public function processTransactionImport(StartTransactionImport $event): void
    {
        $company = $event->company;
        $connectedBankAccountId = $event->connectedBankAccountId;
        $selectedBankAccountId = $event->selectedBankAccountId;
        $startDate = $event->startDate;

        $connectedBankAccount = ConnectedBankAccount::find($connectedBankAccountId);

        if ($connectedBankAccount === null) {
            return;
        }

        $accessToken = $connectedBankAccount->access_token;

        if ($selectedBankAccountId === 'new') {
            $bankAccount = $this->processNewBankAccount($company, $connectedBankAccount, $accessToken);
        } else {
            $bankAccount = BankAccount::find($selectedBankAccountId);

            if ($bankAccount === null) {
                return;
            }
        }

        $connectedBankAccount->update([
            'bank_account_id' => $bankAccount->id,
            'import_transactions' => true,
        ]);
    }

    public function processNewBankAccount(Company $company, ConnectedBankAccount $connectedBankAccount, $accessToken): BankAccount
    {
        $bankAccount = $connectedBankAccount->bankAccount()->create([
            'company_id' => $company->id,
            'institution_id' => $connectedBankAccount->institution_id,
            'type' => $connectedBankAccount->type,
            'number' => $connectedBankAccount->mask,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
        ]);

        $this->mapAccountDetails($bankAccount, $company, $accessToken, $connectedBankAccount);

        return $bankAccount;
    }

    public function mapAccountDetails(BankAccount $bankAccount, Company $company, $accessToken, ConnectedBankAccount $connectedBankAccount): void
    {
        $this->ensureCurrencyExists($company->id, 'USD');

        $accountSubtype = $this->getAccountSubtype($bankAccount->type->value);

        $accountSubtypeId = $this->resolveAccountSubtypeId($company, $accountSubtype);

        $bankAccount->account()->create([
            'company_id' => $company->id,
            'name' => $connectedBankAccount->name,
            'currency_code' => 'USD',
            'description' => $connectedBankAccount->name,
            'subtype_id' => $accountSubtypeId,
            'active' => true,
        ]);
    }

    public function ensureCurrencyExists(int $companyId, string $currencyCode): void
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $hasDefaultCurrency = $defaultCurrency !== null;

        $currency_code = currency($currencyCode);

        Currency::firstOrCreate([
            'company_id' => $companyId,
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

    public function getAccountSubtype(string $plaidType): string
    {
        return match ($plaidType) {
            'depository' => 'Cash and Cash Equivalents',
            'credit' => 'Short-Term Borrowings',
            'loan' => 'Long-Term Borrowings',
            'investment' => 'Long-Term Investments',
            'other' => 'Other Current Assets',
        };
    }

    public function resolveAccountSubtypeId(Company $company, string $accountSubtype): int
    {
        return AccountSubtype::where('company_id', $company->id)
            ->where('name', $accountSubtype)
            ->value('id');
    }
}
