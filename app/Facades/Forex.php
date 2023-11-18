<?php

namespace App\Facades;

use App\Contracts\CurrencyHandler;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static array|null getSupportedCurrencies()
 * @method static array|null getCachedExchangeRates(string $baseCurrency, array $targetCurrencies)
 * @method static float|null getCachedExchangeRate(string $baseCurrency, string $targetCurrency)
 *
 * @see CurrencyHandler
 */
class Forex extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyHandler::class;
    }

    /**
     * Determine if the Currency Exchange Rate feature is disabled.
     */
    public static function isDisabled(): bool
    {
        return ! static::isEnabled();
    }
}
