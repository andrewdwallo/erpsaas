<?php

namespace App\Actions\OptionAction;

use App\Models\Setting\Currency;

class CreateCurrency
{
    public function create(string $code, string $name, string $rate): Currency
    {
        $defaultCurrency = Currency::getDefaultCurrencyCode();

        $hasDefaultCurrency = $defaultCurrency !== null;
        $currency_code = currency($code);

        return Currency::create([
            'name' => $name,
            'code' => $code,
            'rate' => $rate,
            'precision' => $currency_code->getPrecision(),
            'symbol' => $currency_code->getSymbol(),
            'symbol_first' => $currency_code->isSymbolFirst(),
            'decimal_mark' => $currency_code->getDecimalMark(),
            'thousands_separator' => $currency_code->getThousandsSeparator(),
            'enabled' => !$hasDefaultCurrency,
        ]);
    }
}
