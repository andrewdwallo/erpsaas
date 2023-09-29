<?php

namespace App\Utilities;

class CurrencyConverter
{
    public static function convertAndSet($newCurrency, $oldCurrency, $amount): ?string
    {
        if ($newCurrency === null || $oldCurrency === $newCurrency) {
            return null;
        }

        $old_attr = currency($oldCurrency);
        $new_attr = currency($newCurrency);
        $temp_balance = str_replace([$old_attr->getThousandsSeparator(), $old_attr->getDecimalMark()], ['', '.'], $amount);

        return number_format((float) $temp_balance, $new_attr->getPrecision(), $new_attr->getDecimalMark(), $new_attr->getThousandsSeparator());
    }
}
