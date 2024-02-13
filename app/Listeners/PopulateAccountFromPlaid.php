<?php

namespace App\Listeners;

use App\Events\PlaidSuccess;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Banking\Institution;
use App\Models\Company;
use App\Models\Setting\Currency;
use App\Services\PlaidService;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Support\Facades\DB;

class PopulateAccountFromPlaid
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
    public function handle(PlaidSuccess $event): void
    {
        DB::transaction(function () use ($event) {
            $this->processPlaidSuccess($event);
        });
    }

    public function processPlaidSuccess(PlaidSuccess $event): void
    {
        $accessToken = $event->accessToken;

        $company = $event->company;

        $authResponse = $this->plaid->getAccounts($accessToken);

        $institutionResponse = $this->plaid->getInstitution($authResponse->item->institution_id, $company->profile->country);

        $this->processInstitution($authResponse, $institutionResponse, $company);
    }

    public function processInstitution($authResponse, $institutionResponse, Company $company): void
    {
        $institution = Institution::updateOrCreate([
            'external_institution_id' => $authResponse->item->institution_id ?? null,
        ], [
            'name' => $institutionResponse->institution->name ?? null,
            'logo' => $institutionResponse->institution->logo ?? null,
            'website' => $institutionResponse->institution->url ?? null,
        ]);

        foreach ($authResponse->accounts as $plaidAccount) {
            $this->processBankAccount($plaidAccount, $company, $institution, $authResponse);
        }
    }

    public function processBankAccount($plaidAccount, Company $company, Institution $institution, $authResponse): void
    {
        $identifierHash = md5($institution->external_institution_id . $plaidAccount->name . $plaidAccount->mask);

        $bankAccount = BankAccount::updateOrCreate([
            'company_id' => $company->id,
            'identifier' => $identifierHash,
        ], [
            'is_connected_account' => true,
            'external_account_id' => $plaidAccount->account_id,
            'item_id' => $authResponse->item->item_id,
            'enabled' => BankAccount::where('company_id', $company->id)->where('enabled', true)->doesntExist(),
            'type' => $plaidAccount->type,
            'number' => $plaidAccount->mask,
            'institution_id' => $institution->id,
        ]);

        $this->mapAccountDetails($bankAccount, $plaidAccount, $company);
    }

    public function mapAccountDetails(BankAccount $bankAccount, $plaidAccount, Company $company): void
    {
        $this->ensureCurrencyExists($company->id, $plaidAccount->balances->iso_currency_code);

        $accountSubtype = $this->getAccountSubtype($plaidAccount->type);

        $accountSubtypeId = $this->resolveAccountSubtypeId($company, $accountSubtype);

        $bankAccount->account()->updateOrCreate([], [
            'name' => $plaidAccount->name,
            'currency_code' => $plaidAccount->balances->iso_currency_code,
            'description' => $plaidAccount->official_name ?? $plaidAccount->name,
            'subtype_id' => $accountSubtypeId,
            'active' => true,
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

    public function getRoutingNumber($accountId, $numbers): array
    {
        foreach ($numbers as $type => $numberList) {
            foreach ($numberList as $number) {
                if ($number->account_id === $accountId) {
                    return match ($type) {
                        'ach' => ['routing_number' => $number->routing],
                        'bacs' => ['routing_number' => $number->sort_code],
                        'eft' => ['routing_number' => $number->branch],
                        'international' => [
                            'bic' => $number->bic,
                            'iban' => $number->iban,
                        ],
                        default => [],
                    };
                }
            }
        }

        return [];
    }

    public function getFullAccountNumber($accountId, $numbers)
    {
        foreach ($numbers as $numberList) {
            foreach ($numberList as $number) {
                if ($number->account_id === $accountId && property_exists($number, 'account')) {
                    return $number->account;
                }
            }
        }

        return null;
    }
}
