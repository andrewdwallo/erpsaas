<?php

namespace App\Enums\Setting;

use Filament\Support\Contracts\HasLabel;
use NumberFormatter;

enum NumberFormat: string implements HasLabel
{
    case CommaDot = 'comma_dot';
    case DotComma = 'dot_comma';
    case IndianGrouping = 'indian_grouping';

    case ApostropheDot = 'apostrophe_dot';

    case SpaceComma = 'space_comma';
    case SpaceDot = 'space_dot';

    public const DEFAULT = self::CommaDot->value;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CommaDot => '#,###,###.##',
            self::DotComma => '#.###.###,##',
            self::IndianGrouping => '#,##,###.##',
            self::ApostropheDot => '#\'###\'###.##',
            self::SpaceComma => '# ### ###,##',
            self::SpaceDot => '# ### ###.##',
        };
    }

    public function getDecimalMark(): string
    {
        return match ($this) {
            self::CommaDot, self::SpaceDot, self::IndianGrouping, self::ApostropheDot => '.',
            self::DotComma, self::SpaceComma => ',',
        };
    }

    public function getThousandsSeparator(): string
    {
        return match ($this) {
            self::CommaDot, self::IndianGrouping => ',',
            self::DotComma => '.',
            self::SpaceComma, self::SpaceDot => ' ',
            self::ApostropheDot => '\'',
        };
    }

    public function getFormattedExample(): string
    {
        $exampleNumber = 1234567.89;
        $formatter = new NumberFormatter($this->getAssociatedLocale(), NumberFormatter::DECIMAL);

        return $formatter->format($exampleNumber);
    }

    public function getAssociatedLocale(): string
    {
        return match ($this) {
            self::CommaDot => 'en_US',
            self::DotComma => 'de_DE',
            self::IndianGrouping => 'en_IN',
            self::ApostropheDot => 'fr_FR',
            self::SpaceComma => 'fr_CH',
            self::SpaceDot => 'xh_ZA',
        };
    }

    public static function fromLanguageAndCountry(string $language, string $countryCode): string
    {
        $testNumber = 1234567.8912;
        $fullLocale = "{$language}_{$countryCode}";

        $numberFormatter = new NumberFormatter($fullLocale, NumberFormatter::DECIMAL);
        $formattedNumber = $numberFormatter->format($testNumber);

        return self::fromFormattedNumber($formattedNumber);
    }

    public static function fromFormattedNumber(string $formattedNumber): string
    {
        $commaDot = strpos($formattedNumber, '.') && strpos($formattedNumber, ',');
        $dotComma = strpos($formattedNumber, ',') && strpos($formattedNumber, '.');
        $indianGrouping = strpos($formattedNumber, ',') && ! strpos($formattedNumber, '.');
        $apostropheDot = strpos($formattedNumber, '\'') && strpos($formattedNumber, '.');
        $spaceComma = strpos($formattedNumber, ' ') && strpos($formattedNumber, ',');
        $spaceDot = strpos($formattedNumber, ' ') && strpos($formattedNumber, '.');

        return match (true) {
            $commaDot => self::CommaDot->value,
            $dotComma => self::DotComma->value,
            $indianGrouping => self::IndianGrouping->value,
            $apostropheDot => self::ApostropheDot->value,
            $spaceComma => self::SpaceComma->value,
            $spaceDot => self::SpaceDot->value,
            default => self::DEFAULT,
        };
    }

    public function getFormattingParameters(): array
    {
        return [$this->getDecimalMark(), $this->getThousandsSeparator()];
    }
}
