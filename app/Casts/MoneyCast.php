<?php

namespace App\Casts;

use App\Models\Setting\Currency;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use UnexpectedValueException;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): string
    {
        $currency_code = $model->currency_code;

        return money($value, $currency_code)->formatSimple();
    }

    /**
     * @throws UnexpectedValueException
     */
    public function set($model, string $key, $value, array $attributes): int
    {
        if (is_int($value)) {
            return $value;
        }

        $currency_code = $model->currency_code ?? Currency::getDefaultCurrencyCode();

        if (!$currency_code) {
            throw new UnexpectedValueException('Currency code is not set');
        }

        return money($value, $currency_code, true)->getAmount();
    }
}
