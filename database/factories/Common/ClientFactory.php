<?php

namespace Database\Factories\Common;

use App\Models\Common\Address;
use App\Models\Common\Client;
use App\Models\Common\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Client::class;

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
            'currency_code' => 'USD',
            'account_number' => $this->faker->unique()->numerify(str_repeat('#', 12)),
            'website' => $this->faker->url,
            'notes' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function withContacts(int $count = 1): self
    {
        return $this->has(Contact::factory()->count($count));
    }

    public function withPrimaryContact(): self
    {
        return $this->has(Contact::factory()->primary());
    }

    public function withAddresses(): self
    {
        return $this
            ->has(Address::factory()->billing())
            ->has(Address::factory()->shipping());
    }
}
