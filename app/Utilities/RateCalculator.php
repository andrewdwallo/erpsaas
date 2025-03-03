<?php

namespace App\Utilities;

use App\Enums\Setting\NumberFormat;
use App\Models\Setting\Localization;

class RateCalculator
{
    public const PRECISION = 4;

    public const SCALING_FACTOR = 10 ** self::PRECISION;

    public const PERCENTAGE_SCALING_FACTOR = self::SCALING_FACTOR * 100;

    public static function calculatePercentage(int $value, int $scaledRate): int
    {
        return (int) round(($value * $scaledRate) / self::PERCENTAGE_SCALING_FACTOR);
    }

    public static function scaledRateToDecimal(int $scaledRate): float
    {
        return $scaledRate / self::PERCENTAGE_SCALING_FACTOR;
    }

    public static function decimalToScaledRate(float $decimalRate): int
    {
        return (int) round($decimalRate * self::PERCENTAGE_SCALING_FACTOR);
    }

    public static function parseLocalizedRate(string $value): int
    {
        $format = Localization::firstOrFail()->number_format->value;
        [$decimalMark, $thousandsSeparator] = NumberFormat::from($format)->getFormattingParameters();

        $floatValue = (float) str_replace([$thousandsSeparator, $decimalMark], ['', '.'], $value);

        return (int) round($floatValue * self::SCALING_FACTOR);
    }

    public static function formatScaledRate(int $scaledRate): string
    {
        $format = Localization::firstOrFail()->number_format->value;
        [$decimalMark, $thousandsSeparator] = NumberFormat::from($format)->getFormattingParameters();

        $percentageValue = $scaledRate / self::SCALING_FACTOR;

        $formatted = number_format($percentageValue, self::PRECISION, $decimalMark, $thousandsSeparator);
        $formatted = rtrim($formatted, '0');

        return rtrim($formatted, $decimalMark);
    }
}
