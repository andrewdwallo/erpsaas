<?php

namespace Database\Factories\Setting;

use App\Enums\EntityType;
use App\Models\Setting\CompanyProfile;
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
        return [
            'address' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'zip_code' => $this->faker->postcode,
            'country' => $this->faker->randomElement(CompanyProfile::getAvailableCountryCodes()),
            'phone_number' => $this->faker->e164PhoneNumber,
            'email' => $this->faker->email,
            'entity_type' => $this->faker->randomElement(EntityType::class),
            'fiscal_year_start' => (new \DateTime('first day of January'))->format('Y-m-d'),
            'fiscal_year_end' => (new \DateTime('last day of December'))->format('Y-m-d'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CompanyProfile $companyProfile) {
            $companyProfile->timezone = $this->faker->randomElement(CompanyProfile::getTimezoneOptions($companyProfile->country));
            $companyProfile->state = $this->faker->randomElement(CompanyProfile::getStateOptions($companyProfile->country));
            $companyProfile->save();
        });
    }
}
