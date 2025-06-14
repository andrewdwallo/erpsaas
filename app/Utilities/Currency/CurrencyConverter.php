<?php

namespace App\Utilities\Currency;

use App\Facades\Forex;
use Exception;
use Filament\Schemas\Components\Utilities\Set;

class CurrencyConverter
{
    public static function convertAndSet($newCurrency, $oldCurrency, $amount): ?string
    {
        if ($newCurrency === null || $oldCurrency === $newCurrency) {
            return null;
        }

        $old_attr = currency($oldCurrency);
        $new_attr = currency($newCurrency);
        $temp_amount = str_replace([$old_attr->getThousandsSeparator(), $old_attr->getDecimalMark()], ['', '.'], $amount);

        return number_format((float) $temp_amount, $new_attr->getPrecision(), $new_attr->getDecimalMark(), $new_attr->getThousandsSeparator());
    }

    public static function convertBalance(int $amount, string $oldCurrency, string $newCurrency): int
    {
        return money($amount, $oldCurrency)->swapAmountFor($newCurrency);
    }

    public static function prepareForMutator(int $amount, string $currency): string
    {
        return money($amount, $currency)->formatSimple();
    }

    public static function prepareForAccessor(string $amount, string $currency): int
    {
        return self::convertToCents($amount, $currency);
    }

    public static function convertCentsToFormatSimple(int $amount, ?string $currency = null): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        return money($amount, $currency)->formatSimple();
    }

    public static function convertToCents(string | float $amount, ?string $currency = null): int
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        $amountInCents = money($amount, $currency, true)->getAmount();

        if (is_float($amountInCents)) {
            $amountInCents = (int) round($amountInCents);
        }

        return $amountInCents;
    }

    public static function formatCentsToMoney(int $amount, ?string $currency = null, bool $withCode = false): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        $money = money($amount, $currency);

        if ($withCode) {
            return $money->formatWithCode();
        }

        return $money->format();
    }

    public static function formatToMoney(string | float $amount, ?string $currency = null, bool $withCode = false): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        $money = money($amount, $currency, true);

        if ($withCode) {
            return $money->formatWithCode();
        }

        return $money->format();
    }

    public static function convertCentsToFloat(int $amount, ?string $currency = null): float
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        return money($amount, $currency)->getValue();
    }

    public static function convertToFloat(string | float $amount, ?string $currency = null): float
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        return money($amount, $currency, true)->getValue();
    }

    public static function isValidAmount(?string $amount, ?string $currency = null): bool
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        if (blank($amount)) {
            return false;
        }

        try {
            money($amount, $currency);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public static function handleCurrencyChange(Set $set, $state): void
    {
        $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();
        $forexEnabled = Forex::isEnabled();
        $exchangeRate = $forexEnabled ? Forex::getCachedExchangeRate($defaultCurrencyCode, $state) : null;

        if ($forexEnabled && $exchangeRate !== null) {
            $set('rate', $exchangeRate);
        }
    }
}
