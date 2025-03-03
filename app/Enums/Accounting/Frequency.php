<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum Frequency: string implements HasLabel
{
    use ParsesEnum;

    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Custom = 'custom';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getOptions(): array
    {
        return match ($this) {
            self::Weekly => [
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday',
            ],
            self::Monthly, self::Yearly => [
                1 => 'First',
                -1 => 'Last',
                2 => '2nd',
                3 => '3rd',
                4 => '4th',
                5 => '5th',
                6 => '6th',
                7 => '7th',
                8 => '8th',
                9 => '9th',
                10 => '10th',
                11 => '11th',
                12 => '12th',
                13 => '13th',
                14 => '14th',
                15 => '15th',
                16 => '16th',
                17 => '17th',
                18 => '18th',
                19 => '19th',
                20 => '20th',
                21 => '21st',
                22 => '22nd',
                23 => '23rd',
                24 => '24th',
                25 => '25th',
                26 => '26th',
                27 => '27th',
                28 => '28th',
                29 => '29th',
                30 => '30th',
                31 => '31st',
            ],
            default => [],
        };
    }

    public function isDaily(): bool
    {
        return $this === self::Daily;
    }

    public function isWeekly(): bool
    {
        return $this === self::Weekly;
    }

    public function isMonthly(): bool
    {
        return $this === self::Monthly;
    }

    public function isYearly(): bool
    {
        return $this === self::Yearly;
    }

    public function isCustom(): bool
    {
        return $this === self::Custom;
    }
}
