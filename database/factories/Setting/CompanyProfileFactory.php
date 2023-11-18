<?php

namespace Database\Factories\Setting;

use App\Enums\EntityType;
use App\Faker\PhoneNumber;
use App\Faker\State;
use App\Models\Setting\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyProfile>
 */
class CompanyProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
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
            'zip_code' => $this->faker->postcode,
            'email' => $this->faker->email,
            'entity_type' => $this->faker->randomElement(EntityType::class),
        ];
    }

    public function withCountry(string $code): static
    {
        /** @var PhoneNumber $phoneFaker */
        $phoneFaker = $this->faker;

        /** @var State $stateFaker */
        $stateFaker = $this->faker;

        return $this->state([
            'country' => $code,
            'state_id' => $stateFaker->state($code),
            'phone_number' => $phoneFaker->phoneNumberForCountryCode($code),
        ]);
    }
}
