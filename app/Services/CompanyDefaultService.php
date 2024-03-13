<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Setting\Appearance;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Localization;
use App\Models\Setting\Tax;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyDefaultService
{
    public function createCompanyDefaults(Company $company, User $user, string $currencyCode, string $countryCode, string $language): void
    {
        DB::transaction(function () use ($company, $user, $currencyCode, $countryCode, $language) {
            $this->createCurrency($company, $user, $currencyCode);
            $this->createSalesTax($company, $user);
            $this->createPurchaseTax($company, $user);
            $this->createSalesDiscount($company, $user);
            $this->createPurchaseDiscount($company, $user);
            $this->createAppearance($company, $user);
            $this->createDocumentDefaults($company, $user);
            $this->createLocalization($company, $user, $countryCode, $language);
        }, 5);
    }

    private function createCurrency(Company $company, User $user, string $currencyCode): void
    {
        Currency::factory()->forCurrency($currencyCode)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesTax(Company $company, User $user): void
    {
        Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseTax(Company $company, User $user): void
    {
        Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesDiscount(Company $company, User $user): void
    {
        Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseDiscount(Company $company, User $user): void
    {
        Discount::factory()->purchaseDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createAppearance(Company $company, User $user): void
    {
        Appearance::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createDocumentDefaults(Company $company, User $user): void
    {
        DocumentDefault::factory()->invoice()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentDefault::factory()->bill()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createLocalization(Company $company, User $user, string $countryCode, string $language): void
    {
        Localization::factory()->withCountry($countryCode, $language)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
