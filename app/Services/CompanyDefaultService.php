<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Setting\CompanyDefault;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyDefaultService
{
    public function createCompanyDefaults(Company $company, User $user, string $currencyCode, string $countryCode, string $language): void
    {
        DB::transaction(function () use ($user, $company, $currencyCode, $countryCode, $language) {
            // Create the company defaults
            $companyDefaultInstance = CompanyDefault::factory()->withDefault($user, $company, $currencyCode, $countryCode, $language);

            // Create Chart of Accounts
            $chartOfAccountsService = app(ChartOfAccountsService::class);
            $chartOfAccountsService->createChartOfAccounts($company);

            // Get the default bank account and update the company default record
            $defaultBankAccount = $company->bankAccounts()->where('enabled', true)->firstOrFail();

            $companyDefaultInstance->state([
                'bank_account_id' => $defaultBankAccount->id,
            ])->createQuietly();
        });
    }
}
