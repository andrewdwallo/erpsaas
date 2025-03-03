<?php

namespace App\Utilities\Currency;

use Akaunting\Money\Currency as CurrencyBase;
use App\Models\Setting\Currency as CurrencyModel;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;

class ConfigureCurrencies
{
    public static function syncCurrencies(): void
    {
        $currencies = static::fetchCurrencies();

        if ($currencies->isEmpty()) {
            return;
        }

        $customCurrencies = static::formatCurrencies($currencies);
        static::mergeAndSetCurrencies($customCurrencies);

        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        if ($defaultCurrency) {
            config(['money.defaults.currency' => $defaultCurrency]);
        }
    }

    protected static function fetchCurrencies(): Collection
    {
        return CurrencyModel::all();
    }

    protected static function formatCurrencies(Collection $currencies): array
    {
        $customCurrencies = [];

        foreach ($currencies as $currency) {
            /** @var CurrencyModel $currency */
            $customCurrencies[$currency->code] = [
                'name' => $currency->name,
                'rate' => $currency->rate,
                'precision' => $currency->precision,
                'symbol' => $currency->symbol,
                'symbol_first' => $currency->symbol_first,
                'decimal_mark' => $currency->decimal_mark,
                'thousands_separator' => $currency->thousands_separator,
            ];
        }

        return $customCurrencies;
    }

    protected static function mergeAndSetCurrencies(array $customCurrencies): void
    {
        $existingCurrencies = CurrencyBase::getCurrencies();

        foreach ($existingCurrencies as $code => $currency) {
            try {
                $name = Currencies::getName($code, app()->getLocale());
                $existingCurrencies[$code]['name'] = ucwords($name);
            } catch (MissingResourceException) {
                $existingCurrencies[$code]['name'] = $currency['name'];
            }
        }

        $mergedCurrencies = array_replace_recursive($existingCurrencies, $customCurrencies);

        CurrencyBase::setCurrencies($mergedCurrencies);
    }
}
