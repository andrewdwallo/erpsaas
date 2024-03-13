<?php

namespace Database\Factories\Setting;

use App\Faker\CurrencyCode;
use App\Models\Company;
use App\Models\Setting\Appearance;
use App\Models\Setting\CompanyDefault;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Localization;
use App\Models\Setting\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyDefault>
 */
class CompanyDefaultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CompanyDefault::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            //
        ];
    }

    public function withDefault(User $user, Company $company, string $country, string $language = 'en'): static
    {
        /** @var CurrencyCode $currencyFaker */
        $currencyFaker = $this->faker;

        $currencyCode = $currencyFaker->currencyCode($country);

        $currency = $this->createCurrency($company, $user, $currencyCode);
        $salesTax = $this->createSalesTax($company, $user);
        $purchaseTax = $this->createPurchaseTax($company, $user);
        $salesDiscount = $this->createSalesDiscount($company, $user);
        $purchaseDiscount = $this->createPurchaseDiscount($company, $user);
        $this->createAppearance($company, $user);
        $this->createDocumentDefaults($company, $user);
        $this->createLocalization($company, $user, $country, $language);

        $companyDefaults = [
            'company_id' => $company->id,
            'currency_code' => $currency->code,
            'sales_tax_id' => $salesTax->id,
            'purchase_tax_id' => $purchaseTax->id,
            'sales_discount_id' => $salesDiscount->id,
            'purchase_discount_id' => $purchaseDiscount->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        return $this->state($companyDefaults);
    }

    private function createCurrency(Company $company, User $user, string $currencyCode)
    {
        return Currency::factory()->forCurrency($currencyCode)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesTax(Company $company, User $user): Tax
    {
        return Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseTax(Company $company, User $user): Tax
    {
        return Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesDiscount(Company $company, User $user): Discount
    {
        return Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseDiscount(Company $company, User $user): Discount
    {
        return Discount::factory()->purchaseDiscount()->create([
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
