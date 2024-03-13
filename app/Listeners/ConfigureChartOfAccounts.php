<?php

namespace App\Listeners;

use App\Enums\Accounting\AccountType;
use App\Events\CompanyGenerated;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Company;
use App\Utilities\Currency\CurrencyAccessor;

class ConfigureChartOfAccounts
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyGenerated $event): void
    {
        $company = $event->company;

        $this->createChartOfAccounts($company);
    }

    public function createChartOfAccounts(Company $company): void
    {
        $chartOfAccounts = config('chart-of-accounts.default');

        foreach ($chartOfAccounts as $type => $subtypes) {
            foreach ($subtypes as $subtypeName => $subtypeConfig) {
                $subtype = AccountSubtype::create([
                    'company_id' => $company->id,
                    'multi_currency' => $subtypeConfig['multi_currency'] ?? false,
                    'category' => AccountType::from($type)->getCategory()->value,
                    'type' => $type,
                    'name' => $subtypeName,
                    'description' => $subtypeConfig['description'] ?? 'No description available.',
                ]);

                $this->createDefaultAccounts($company, $subtype, $subtypeConfig);
            }
        }
    }

    private function createDefaultAccounts(Company $company, AccountSubtype $subtype, array $subtypeConfig): void
    {
        if (isset($subtypeConfig['accounts']) && is_array($subtypeConfig['accounts'])) {
            $baseCode = $subtypeConfig['base_code'];

            foreach ($subtypeConfig['accounts'] as $accountName => $accountDetails) {
                Account::create([
                    'company_id' => $company->id,
                    'category' => $subtype->type->getCategory()->value,
                    'type' => $subtype->type->value,
                    'subtype_id' => $subtype->id,
                    'code' => $baseCode++,
                    'name' => $accountName,
                    'description' => $accountDetails['description'] ?? 'No description available.',
                    'ending_balance' => 0,
                    'active' => true,
                    'default' => true,
                    'currency_code' => CurrencyAccessor::getDefaultCurrency(),
                    'created_by' => $company->owner->id,
                    'updated_by' => $company->owner->id,
                ]);
            }
        }
    }
}
