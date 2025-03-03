<?php

namespace Database\Factories\Setting;

use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $defaultCurrency = currency('USD');

        return [
            'name' => $defaultCurrency->getName(),
            'code' => $defaultCurrency->getCurrency(),
            'rate' => $defaultCurrency->getRate(),
            'precision' => $defaultCurrency->getPrecision(),
            'symbol' => $defaultCurrency->getSymbol(),
            'symbol_first' => $defaultCurrency->isSymbolFirst(),
            'decimal_mark' => $defaultCurrency->getDecimalMark(),
            'thousands_separator' => $defaultCurrency->getThousandsSeparator(),
            'enabled' => false,
        ];
    }

    /**
     * Define a state for a specific currency.
     */
    public function forCurrency(string $code, ?float $rate = null): static
    {
        $currency = currency($code);

        return $this->state([
            'name' => $currency->getName(),
            'code' => $currency->getCurrency(),
            'rate' => $rate ?? $currency->getRate(),
            'precision' => $currency->getPrecision(),
            'symbol' => $currency->getSymbol(),
            'symbol_first' => $currency->isSymbolFirst(),
            'decimal_mark' => $currency->getDecimalMark(),
            'thousands_separator' => $currency->getThousandsSeparator(),
            'enabled' => false,
        ]);
    }
}
