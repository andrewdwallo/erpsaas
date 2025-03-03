<?php

namespace Database\Factories\Banking;

use App\Enums\Banking\BankAccountType;
use App\Models\Banking\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'type' => BankAccountType::Depository,
            'number' => $this->faker->unique()->numerify(str_repeat('#', 12)),
            'enabled' => false,
        ];
    }
}
