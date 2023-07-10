<?php

namespace Database\Factories;

use App\Models\Setting\Currency;
use Config;
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
        $defaultCurrency = Config::get('money.USD');

        return [
            'name' => $defaultCurrency['name'],
            'code' => 'USD',
            'rate' => 1,
            'precision' => $defaultCurrency['precision'],
            'symbol' => $defaultCurrency['symbol'],
            'symbol_first' => $defaultCurrency['symbol_first'],
            'decimal_mark' => $defaultCurrency['decimal_mark'],
            'thousands_separator' => $defaultCurrency['thousands_separator'],
            'enabled' => true,
        ];
    }
}
