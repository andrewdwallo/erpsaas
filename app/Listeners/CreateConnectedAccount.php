<?php

namespace App\Listeners;

use App\Events\PlaidSuccess;
use App\Models\Banking\Institution;
use App\Models\Company;
use App\Services\PlaidService;
use Illuminate\Support\Facades\DB;

class CreateConnectedAccount
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

        $this->processInstitution($authResponse, $institutionResponse, $company, $accessToken);
    }

    public function processInstitution($authResponse, $institutionResponse, Company $company, $accessToken): void
    {
        $institution = Institution::updateOrCreate([
            'external_institution_id' => $authResponse->item->institution_id ?? null,
        ], [
            'name' => $institutionResponse->institution->name ?? null,
            'logo' => $institutionResponse->institution->logo ?? null,
            'website' => $institutionResponse->institution->url ?? null,
        ]);

        foreach ($authResponse->accounts as $plaidAccount) {
            $this->processConnectedBankAccount($plaidAccount, $company, $institution, $authResponse, $accessToken);
        }
    }

    public function processConnectedBankAccount($plaidAccount, Company $company, Institution $institution, $authResponse, $accessToken): void
    {
        $identifierHash = md5($institution->external_institution_id . $plaidAccount->name . $plaidAccount->mask);

        $company->connectedBankAccounts()->updateOrCreate([
            'identifier' => $identifierHash,
        ], [
            'institution_id' => $institution->id,
            'external_account_id' => $plaidAccount->account_id,
            'access_token' => $accessToken,
            'item_id' => $authResponse->item->item_id,
            'currency_code' => $plaidAccount->balances->iso_currency_code ?? 'USD',
            'current_balance' => $plaidAccount->balances->current ?? 0,
            'name' => $plaidAccount->name,
            'mask' => $plaidAccount->mask,
            'type' => $plaidAccount->type,
            'subtype' => $plaidAccount->subtype,
            'import_transactions' => false,
        ]);
    }
}
