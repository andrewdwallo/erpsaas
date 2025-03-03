<?php

namespace App\Collections\Accounting;

use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Database\Eloquent\Collection;

class DocumentCollection extends Collection
{
    public function sumMoneyInCents(string $column): int
    {
        return $this->reduce(static function ($carry, $document) use ($column) {
            return $carry + $document->getRawOriginal($column);
        }, 0);
    }

    public function sumMoneyFormattedSimple(string $column, ?string $currency = null): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        $totalCents = $this->sumMoneyInCents($column);

        return CurrencyConverter::convertCentsToFormatSimple($totalCents, $currency);
    }

    public function sumMoneyFormatted(string $column, ?string $currency = null): string
    {
        $currency ??= CurrencyAccessor::getDefaultCurrency();

        $totalCents = $this->sumMoneyInCents($column);

        return CurrencyConverter::formatCentsToMoney($totalCents, $currency);
    }

    public function sumMoneyInDefaultCurrency(string $column): int
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

        return $this->reduce(static function ($carry, $document) use ($column, $defaultCurrency) {
            $amountInCents = $document->getRawOriginal($column);
            $documentCurrency = $document->currency_code ?? $defaultCurrency;

            if ($documentCurrency !== $defaultCurrency) {
                $amountInCents = CurrencyConverter::convertBalance(
                    $amountInCents,
                    $documentCurrency,
                    $defaultCurrency
                );
            }

            return $carry + $amountInCents;
        }, 0);
    }
}
