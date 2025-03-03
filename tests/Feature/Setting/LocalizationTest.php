<?php

use App\Models\Setting\Localization;
use App\Utilities\RateCalculator;

// RateCalculator Basic Operations
it('calculates percentage correctly', function () {
    $valueInCents = 100000; // 1000 dollars in cents
    $scaledRate = RateCalculator::decimalToScaledRate(0.025); // 2.5%

    expect(RateCalculator::calculatePercentage($valueInCents, $scaledRate))
        ->toBe(2500); // Should be 25 dollars in cents
});

it('converts between scaled rates and decimals correctly', function (float $decimal, int $scaled) {
    // Test decimal to scaled
    expect(RateCalculator::decimalToScaledRate($decimal))->toBe($scaled)
        ->and(RateCalculator::scaledRateToDecimal($scaled))->toBe($decimal);
})->with([
    [0.25, 250000],     // 0.25 * 1000000 = 250000
    [0.1, 100000],      // 0.1 * 1000000 = 100000
    [0.01, 10000],      // 0.01 * 1000000 = 10000
    [0.001, 1000],      // 0.001 * 1000000 = 1000
    [0.0001, 100],      // 0.0001 * 1000000 = 100
]);

it('handles rate formatting correctly for different computations', function () {
    $localization = Localization::firstOrFail();
    $localization->update(['language' => 'en']);

    // Test fixed amount formatting
    expect(rateFormat(100000, 'fixed', 'USD'))->toBe('$100,000.00 USD');

    // Test percentage formatting
    $scaledRate = RateCalculator::decimalToScaledRate(0.000025); // 0.25%
    expect(rateFormat($scaledRate, 'percentage'))->toBe('25%');
});

// Edge Cases and Error Handling
it('handles edge cases correctly', function () {
    $localization = Localization::firstOrFail();
    $localization->update(['language' => 'en']);

    expect(RateCalculator::formatScaledRate(0))->toBe('0')
        ->and(RateCalculator::formatScaledRate(1))->toBe('0.0001')
        ->and(RateCalculator::formatScaledRate(10000000))->toBe('1,000')
        ->and(RateCalculator::formatScaledRate(-250000))->toBe('-25');
});

// Precision Tests
it('maintains precision correctly', function () {
    $localization = Localization::firstOrFail();
    $localization->update(['language' => 'en']);

    $testCases = [
        '1.0000' => '1',
        '1.2300' => '1.23',
        '1.2340' => '1.234',
        '1.2345' => '1.2345',
    ];

    foreach ($testCases as $input => $expected) {
        $scaled = RateCalculator::parseLocalizedRate($input);
        expect(RateCalculator::formatScaledRate($scaled))->toBe($expected);
    }
});
