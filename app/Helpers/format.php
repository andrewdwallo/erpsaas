<?php

use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Setting\NumberFormat;
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
        $computationEnum = AdjustmentComputation::parse($computation);
        $localization = Localization::firstOrFail();

        if ($computationEnum->isFixed() && currency($currency)->isSymbolFirst()) {
            return currency($currency)->getPrefix();
        }

        if ($computationEnum->isPercentage() && $localization->percent_first) {
            return '%';
        }

        return null;
    }
}

if (! function_exists('rateSuffix')) {
    function rateSuffix($computation, ?string $currency = null): ?string
    {
        $computationEnum = AdjustmentComputation::parse($computation);
        $localization = Localization::firstOrFail();

        if ($computationEnum->isFixed() && ! currency($currency)->isSymbolFirst()) {
            return currency($currency)->getSuffix();
        }

        if ($computationEnum->isPercentage() && ! $localization->percent_first) {
            return '%';
        }

        return null;
    }
}

if (! function_exists('rateMask')) {
    function rateMask($computation, ?string $currency = null): ?RawJs
    {
        $computationEnum = AdjustmentComputation::parse($computation);

        if ($computationEnum->isPercentage()) {
            return percentMask(4);
        }

        if ($computationEnum->isFixed()) {
            $precision = currency($currency)->getPrecision();

            return RawJs::make(generateJsCode($precision, $currency));
        }

        return null;
    }
}

if (! function_exists('rateFormat')) {
    function rateFormat($state, $computation, ?string $currency = null): ?string
    {
        if (blank($state)) {
            return null;
        }

        $computationEnum = AdjustmentComputation::parse($computation);
        $localization = Localization::firstOrFail();

        if ($computationEnum->isPercentage() && $localization->percent_first) {
            return '%' . $state;
        }

        if ($computationEnum->isPercentage() && ! $localization->percent_first) {
            return $state . '%';
        }

        if ($computationEnum->isFixed()) {
            return money($state, $currency, true)->formatWithCode();
        }

        return null;
    }
}
