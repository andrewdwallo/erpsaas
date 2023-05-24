<?php

namespace Database\Factories;

use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency::class;


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = config('money');

        $existingCodes = Currency::query()->pluck('code')->toArray();

        foreach ($existingCodes as $code) {
            unset($currencies[$code]);
        }

        $randomCode = $this->faker->randomElement(array_keys($currencies));

        $code = $randomCode;

        $currency = $currencies[$randomCode];

        return [
            'name' => $currency['name'],
            'code' => $code,
            'rate' => $this->faker->randomFloat($currency['precision'], 1, 10),
            'precision' => $currency['precision'],
            'symbol' => $currency['symbol'],
            'symbol_first' => $currency['symbol_first'],
            'decimal_mark' => $currency['decimal_mark'],
            'thousands_separator' => $currency['thousands_separator'],
            'enabled' => $this->faker->boolean,
            'company_id' => $this->company->id,
        ];
    }

    /**
     * Indicate that the currency is enabled.
     */
    public function enabled(): Factory
    {
        return $this->state(static function (array $attributes) {
            return [
                'enabled' => true,
            ];
        });
    }

    /**
     * Indicate that the currency is disabled.
     */
    public function disabled(): Factory
    {
        return $this->state(static function (array $attributes) {
            return [
                'enabled' => false,
            ];
        });
    }
}
