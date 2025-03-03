<?php

namespace App\Casts;

use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class DocumentMoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $currency_code = $attributes['currency_code'] ?? CurrencyAccessor::getDefaultCurrency();

        if ($value !== null) {
            return CurrencyConverter::convertCentsToFloat($value, $currency_code);
        }

        return 0.0;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $currency_code = $attributes['currency_code'] ?? CurrencyAccessor::getDefaultCurrency();

        if (is_numeric($value)) {
            $value = (string) $value;
        } elseif (! is_string($value)) {
            throw new UnexpectedValueException('Expected string or numeric value for money cast');
        }

        return CurrencyConverter::prepareForAccessor($value, $currency_code);
    }
}
