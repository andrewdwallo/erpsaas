<?php

namespace Database\Factories\Setting;

use App\Enums\Setting\EntityType;
use App\Faker\State;
use App\Models\Common\Address;
use App\Models\Company;
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
            'phone_number' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'entity_type' => $this->faker->randomElement(EntityType::class),
        ];
    }

    public function forCompany(Company $company): self
    {
        return $this->state([
            'company_id' => $company->id,
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }

    public function withAddress(): self
    {
        return $this->has(Address::factory()->general());
    }
}
