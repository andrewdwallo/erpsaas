<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CurrencyRateCast implements CastsAttributes
{
    private const SCALE = 8;

    public function get($model, string $key, $value, array $attributes): float
    {
        $floatValue = $value / (10 ** self::SCALE);

        $strValue = rtrim(rtrim(number_format($floatValue, self::SCALE, '.', ''), '0'), '.');

        return (float) $strValue;
    }

    public function set($model, string $key, $value, array $attributes): int
    {
        return (int) round($value * (10 ** self::SCALE));
    }
}
