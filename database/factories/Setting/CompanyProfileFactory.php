<?php

namespace Database\Factories\Setting;

use App\Enums\EntityType;
use App\Events\CompanyGenerated;
use App\Faker\{PhoneNumber, State};
use App\Models\Setting\CompanyProfile;
use DateTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    /**
     * @var string The related model's name.
     */
    protected $model = CompanyProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var PhoneNumber $phoneFaker */
        $phoneFaker = $this->faker;

        /** @var State $stateFaker */
        $stateFaker = $this->faker;

        $countryCode = $this->faker->countryCode;

        return [
            'address' => $this->faker->streetAddress,
            'zip_code' => $this->faker->postcode,
            'state_id' => $stateFaker->state($countryCode),
            'country' => $countryCode,
            'timezone' => $this->faker->timezone($countryCode),
            'phone_number' => $phoneFaker->phoneNumberForCountryCode($countryCode),
            'email' => $this->faker->email,
            'entity_type' => $this->faker->randomElement(EntityType::class),
            'fiscal_year_start' => (new DateTime('first day of January'))->format('Y-m-d'),
            'fiscal_year_end' => (new DateTime('last day of December'))->format('Y-m-d'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(static function (CompanyProfile $companyProfile) {
            $companyProfile->save();

            event(new CompanyGenerated($companyProfile->company->owner, $companyProfile->company, $companyProfile->country));
        });
    }
}
