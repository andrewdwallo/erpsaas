<?php

namespace App\Actions\OptionAction;

use App\Models\Setting\Currency;
use App\Utilities\Currency\CurrencyAccessor;

class CreateCurrency
{
    public static function create(string $code, string $name, string $rate): Currency
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        $hasDefaultCurrency = $defaultCurrency !== null;
        $currency = currency($code);

        return Currency::create([
            'name' => $name,
            'code' => $code,
            'rate' => $rate,
            'precision' => $currency->getPrecision(),
            'symbol' => $currency->getSymbol(),
            'symbol_first' => $currency->isSymbolFirst(),
            'decimal_mark' => $currency->getDecimalMark(),
            'thousands_separator' => $currency->getThousandsSeparator(),
            'enabled' => ! $hasDefaultCurrency,
        ]);
    }
}
