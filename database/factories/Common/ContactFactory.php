<?php

namespace Database\Factories\Common;

use App\Models\Common\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phones' => $this->generatePhones(),
            'is_primary' => $this->faker->boolean(50),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    protected function generatePhones(): array
    {
        $phones = [];

        if ($this->faker->boolean(80)) {
            $phones[] = [
                'data' => ['number' => $this->faker->phoneNumber],
                'type' => 'primary',
            ];
        }

        if ($this->faker->boolean(50)) {
            $phones[] = [
                'data' => ['number' => $this->faker->phoneNumber],
                'type' => 'mobile',
            ];
        }

        if ($this->faker->boolean(30)) {
            $phones[] = [
                'data' => ['number' => $this->faker->phoneNumber],
                'type' => 'toll_free',
            ];
        }

        if ($this->faker->boolean(10)) {
            $phones[] = [
                'data' => ['number' => $this->faker->phoneNumber],
                'type' => 'fax',
            ];
        }

        return $phones;
    }

    public function primary(): self
    {
        return $this->state([
            'is_primary' => true,
        ]);
    }

    public function secondary(): self
    {
        return $this->state([
            'is_primary' => false,
        ]);
    }
}
