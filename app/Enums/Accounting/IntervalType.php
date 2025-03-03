<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum IntervalType: string implements HasLabel
{
    use ParsesEnum;

    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Day => 'Day(s)',
            self::Week => 'Week(s)',
            self::Month => 'Month(s)',
            self::Year => 'Year(s)',
        };
    }

    public function getSingularLabel(): ?string
    {
        return match ($this) {
            self::Day => 'Day',
            self::Week => 'Week',
            self::Month => 'Month',
            self::Year => 'Year',
        };
    }

    public function getPluralLabel(): ?string
    {
        return match ($this) {
            self::Day => 'Days',
            self::Week => 'Weeks',
            self::Month => 'Months',
            self::Year => 'Years',
        };
    }

    public function isDay(): bool
    {
        return $this === self::Day;
    }

    public function isWeek(): bool
    {
        return $this === self::Week;
    }

    public function isMonth(): bool
    {
        return $this === self::Month;
    }

    public function isYear(): bool
    {
        return $this === self::Year;
    }
}
