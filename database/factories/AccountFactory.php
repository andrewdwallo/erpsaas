<?php

namespace Database\Factories;

use App\Models\Banking\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['bank', 'card'];

        return [
            'type' => $this->faker->randomElement($types),
            'name' => $this->faker->text(15),
            'number' => (string) $this->faker->randomNumber(12, true),
            'currency_code' => $this->company->currencies()->enabled()->get()->random(1)->pluck('code')->first(),
            'opening_balance' => '0',
            'bank_name' => $this->faker->text(15),
            'bank_phone' => $this->faker->phoneNumber,
            'bank_address' => $this->faker->address,
            'enabled' => $this->faker->boolean,
            'company_id' => $this->company->id,
        ];
    }

    /**
     * Indicate that the model is enabled.
     *
     * @return Factory<Account>
     */
    public function enabled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => true,
            ];
        });
    }

    /**
     * Indicate that the model is disabled.
     *
     * @return Factory<Account>
     */
    public function disabled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => false,
            ];
        });
    }

    /**
     * Indicate that the default currency is used.
     *
     * @return Factory<Account>
     */
    public function default_currency(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'currency_code' => $this->default_currency(),
            ];
        });
    }
}
