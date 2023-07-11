<?php

namespace App\Actions\OptionAction;

use App\Models\Setting\Currency;
use Illuminate\Support\Facades\Auth;

class CreateCurrency
{
    public function create(string $code, string $name, string $rate): Currency
    {
        $companyId = Auth::user()->currentCompany->id;

        $defaultCurrency = Currency::getDefaultCurrency();

        $hasDefaultCurrency = $defaultCurrency !== null;

        return Currency::create([
            'name' => $name,
            'code' => $code,
            'rate' => $rate,
            'precision' => config("money.{$code}.precision"),
            'symbol' => config("money.{$code}.symbol"),
            'symbol_first' => config("money.{$code}.symbol_first"),
            'decimal_mark' => config("money.{$code}.decimal_mark"),
            'thousands_separator' => config("money.{$code}.thousands_separator"),
            'enabled' => !$hasDefaultCurrency,
            'company_id' => $companyId,
        ]);
    }
}
