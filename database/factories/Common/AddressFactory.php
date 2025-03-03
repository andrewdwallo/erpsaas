<?php

namespace Database\Factories\Common;

use App\Enums\Common\AddressType;
use App\Models\Common\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'type' => $this->faker->randomElement(AddressType::cases()),
            'recipient' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'address_line_1' => $this->faker->streetAddress,
            'address_line_2' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state_id' => $this->faker->state('US'),
            'postal_code' => $this->faker->postcode,
            'country_code' => 'US',
            'notes' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function billing(): self
    {
        return $this->state([
            'type' => AddressType::Billing,
        ]);
    }

    public function shipping(): self
    {
        return $this->state([
            'type' => AddressType::Shipping,
        ]);
    }

    public function general(): self
    {
        return $this->state([
            'type' => AddressType::General,
        ]);
    }
}
