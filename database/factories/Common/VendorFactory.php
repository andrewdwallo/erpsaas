<?php

namespace Database\Factories\Common;

use App\Enums\Common\ContractorType;
use App\Enums\Common\VendorType;
use App\Models\Common\Address;
use App\Models\Common\Contact;
use App\Models\Common\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->company,
            'type' => $this->faker->randomElement(VendorType::cases()),
            'contractor_type' => function (array $attributes) {
                return $attributes['type'] === VendorType::Contractor ? $this->faker->randomElement(ContractorType::cases()) : null;
            },
            'ssn' => function (array $attributes) {
                return $attributes['contractor_type'] === ContractorType::Individual ? $this->faker->numerify(str_repeat('#', 9)) : null;
            },
            'ein' => function (array $attributes) {
                return $attributes['contractor_type'] === ContractorType::Business ? $this->faker->numerify(str_repeat('#', 9)) : null;
            },
            'currency_code' => 'USD',
            'account_number' => $this->faker->unique()->numerify(str_repeat('#', 12)),
            'website' => $this->faker->url,
            'notes' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function regular(): self
    {
        return $this->state([
            'type' => VendorType::Regular,
        ]);
    }

    public function contractor(): self
    {
        return $this->state([
            'type' => VendorType::Contractor,
        ]);
    }

    public function individualContractor(): self
    {
        return $this->state([
            'type' => VendorType::Contractor,
            'contractor_type' => ContractorType::Individual,
        ]);
    }

    public function businessContractor(): self
    {
        return $this->state([
            'type' => VendorType::Contractor,
            'contractor_type' => ContractorType::Business,
        ]);
    }

    public function withContact(): self
    {
        return $this->has(Contact::factory()->primary());
    }

    public function withAddress(): self
    {
        return $this->has(Address::factory()->general());
    }
}
