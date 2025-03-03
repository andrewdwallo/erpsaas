<?php

namespace Database\Factories\Setting;

use App\Faker\CurrencyCode;
use App\Models\Company;
use App\Models\Setting\CompanyDefault;
use App\Models\Setting\Currency;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Localization;
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

    public function withDefault(User $user, Company $company, ?string $currencyCode, string $countryCode, string $language = 'en'): static
    {
        if ($currencyCode === null) {
            /** @var CurrencyCode $currencyFaker */
            $currencyFaker = $this->faker;
            $currencyCode = $currencyFaker->currencyCode($countryCode);
        }

        $currency = $this->createCurrency($company, $user, $currencyCode);
        $this->createDocumentDefaults($company, $user);
        $this->createLocalization($company, $user, $countryCode, $language);

        $companyDefaults = [
            'company_id' => $company->id,
            'currency_code' => $currency->code,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];

        return $this->state($companyDefaults);
    }

    private function createCurrency(Company $company, User $user, string $currencyCode): Currency
    {
        return Currency::factory()->forCurrency($currencyCode)->createQuietly([
            'company_id' => $company->id,
            'enabled' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createDocumentDefaults(Company $company, User $user): void
    {
        DocumentDefault::factory()->invoice()->createQuietly([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentDefault::factory()->bill()->createQuietly([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentDefault::factory()->estimate()->createQuietly([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createLocalization(Company $company, User $user, string $countryCode, string $language): void
    {
        Localization::factory()->withCountry($countryCode, $language)->createQuietly([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
