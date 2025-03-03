<?php

namespace App\Casts;

use App\Enums\Accounting\AdjustmentComputation;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\RateCalculator;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RateCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): string
    {
        if (! $value) {
            return '0';
        }

        $currency_code = $this->getDefaultCurrencyCode();
        $computation = AdjustmentComputation::parse($attributes['computation'] ?? $attributes['discount_computation'] ?? null);

        if ($computation?->isFixed()) {
            return money($value, $currency_code)->formatSimple();
        }

        return RateCalculator::formatScaledRate($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        if (! $value) {
            return 0;
        }

        if (is_int($value)) {
            return $value;
        }

        $computation = AdjustmentComputation::parse($attributes['computation'] ?? $attributes['discount_computation'] ?? null);

        $currency_code = $this->getDefaultCurrencyCode();

        if ($computation?->isFixed()) {
            return money($value, $currency_code, true)->getAmount();
        }

        return RateCalculator::parseLocalizedRate($value);
    }

    private function getDefaultCurrencyCode(): string
    {
        return CurrencyAccessor::getDefaultCurrency();
    }
}
