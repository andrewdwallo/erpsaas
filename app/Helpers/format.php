<?php

use App\Enums\NumberFormat;
use App\Models\Setting\Localization;
use Filament\Support\RawJs;

if (! function_exists('generateJsCode')) {
    function generateJsCode(string $precision, ?string $currency = null): string
    {
        $decimal_mark = currency($currency)->getDecimalMark();
        $thousands_separator = currency($currency)->getThousandsSeparator();

        return "\$money(\$input, '" . $decimal_mark . "', '" . $thousands_separator . "', " . $precision . ');';
    }
}

if (! function_exists('generatePercentJsCode')) {
    function generatePercentJsCode(string $format, int $precision): string
    {
        [$decimal_mark, $thousands_separator] = NumberFormat::from($format)->getFormattingParameters();

        return "\$money(\$input, '" . $decimal_mark . "', '" . $thousands_separator . "', " . $precision . ');';
    }
}

if (! function_exists('moneyMask')) {
    function moneyMask(?string $currency = null): RawJs
    {
        $precision = currency($currency)->getPrecision();

        return RawJs::make(generateJsCode($precision, $currency));
    }
}

if (! function_exists('percentMask')) {
    function percentMask(int $precision = 4): RawJs
    {
        $format = Localization::firstOrFail()->number_format->value;

        return RawJs::make(generatePercentJsCode($format, $precision));
    }
}

if (! function_exists('ratePrefix')) {
    function ratePrefix($computation, ?string $currency = null): ?string
    {
        if ($computation instanceof BackedEnum) {
            $computation = $computation->value;
        }

        if ($computation === 'fixed') {
            return currency($currency)->getCodePrefix();
        }

        if ($computation === 'percentage' || $computation === 'compound') {
            $percent_first = Localization::firstOrFail()->percent_first;

            return $percent_first ? '%' : null;
        }

        return null;
    }
}

if (! function_exists('rateSuffix')) {
    function rateSuffix($computation, ?string $currency = null): ?string
    {
        if ($computation instanceof BackedEnum) {
            $computation = $computation->value;
        }

        if ($computation === 'percentage' || $computation === 'compound') {
            $percent_first = Localization::firstOrFail()->percent_first;

            return $percent_first ? null : '%';
        }

        if ($computation === 'fixed') {
            return currency($currency)->getCodeSuffix();
        }

        return null;
    }
}

if (! function_exists('rateMask')) {
    function rateMask($computation, ?string $currency = null): RawJs
    {
        if ($computation instanceof BackedEnum) {
            $computation = $computation->value;
        }

        if ($computation === 'percentage' || $computation === 'compound') {
            return percentMask(4);
        }

        $precision = currency($currency)->getPrecision();

        return RawJs::make(generateJsCode($precision, $currency));
    }
}

if (! function_exists('rateFormat')) {
    function rateFormat($state, $computation, ?string $currency = null): ?string
    {
        if (blank($state)) {
            return null;
        }

        if ($computation instanceof BackedEnum) {
            $computation = $computation->value;
        }

        if ($computation === 'percentage' || $computation === 'compound') {
            $percent_first = Localization::firstOrFail()->percent_first;

            if ($percent_first) {
                return '%' . $state;
            }

            return $state . '%';
        }

        if ($computation === 'fixed') {
            return money($state, $currency, true)->formatWithCode();
        }

        return null;
    }
}
