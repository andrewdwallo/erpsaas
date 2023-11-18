<?php

namespace App\Casts;

use App\Enums\NumberFormat;
use App\Models\Setting\Localization;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RateCast implements CastsAttributes
{
    private const PRECISION = 4;

    public function get($model, string $key, $value, array $attributes): string
    {
        $currency_code = $this->getDefaultCurrencyCode();
        $computation = $attributes['computation'] ?? null;

        if ($computation === 'fixed') {
            return money($value, $currency_code)->formatSimple();
        }

        $floatValue = $value / (10 ** self::PRECISION);

        $format = Localization::firstOrFail()->number_format->value;
        [$decimal_mark, $thousands_separator] = NumberFormat::from($format)->getFormattingParameters();

        return $this->formatWithoutTrailingZeros($floatValue, $decimal_mark, $thousands_separator);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if (is_int($value)) {
            return $value;
        }

        $computation = $attributes['computation'] ?? null;

        $currency_code = $this->getDefaultCurrencyCode();

        if ($computation === 'fixed') {
            return money($value, $currency_code, true)->getAmount();
        }

        $format = Localization::firstOrFail()->number_format->value;
        [$decimal_mark, $thousands_separator] = NumberFormat::from($format)->getFormattingParameters();

        $intValue = str_replace([$thousands_separator, $decimal_mark], ['', '.'], $value);

        return (int) round((float) $intValue * (10 ** self::PRECISION));
    }

    private function getDefaultCurrencyCode(): string
    {
        return CurrencyAccessor::getDefaultCurrency();
    }

    private function formatWithoutTrailingZeros($floatValue, $decimal_mark, $thousands_separator): string
    {
        $formatted = number_format($floatValue, self::PRECISION, $decimal_mark, $thousands_separator);
        $formatted = rtrim($formatted, '0');

        return rtrim($formatted, $decimal_mark);
    }
}
