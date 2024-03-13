<?php

namespace App\Repositories\Setting;

use App\Models\Company;
use App\Models\Setting\Currency;

class CurrencyRepository
{
    public function ensureCurrencyExists(Company $company, string $currencyCode): Currency
    {
        $hasDefaultCurrency = $this->hasDefaultCurrency($company);

        $currency = currency($currencyCode);

        return $company->currencies()
            ->firstOrCreate([
                'code' => $currencyCode,
            ], [
                'name' => $currency->getName(),
                'rate' => $currency->getRate(),
                'precision' => $currency->getPrecision(),
                'symbol' => $currency->getSymbol(),
                'symbol_first' => $currency->isSymbolFirst(),
                'decimal_mark' => $currency->getDecimalMark(),
                'thousands_separator' => $currency->getThousandsSeparator(),
                'enabled' => ! $hasDefaultCurrency,
            ]);
    }

    public function getDefaultCurrency(Company $company): ?Currency
    {
        return $company->currencies()
            ->where('enabled', true)
            ->first();
    }

    public function hasDefaultCurrency(Company $company): bool
    {
        return $this->getDefaultCurrency($company) !== null;
    }
}
