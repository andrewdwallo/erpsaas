<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum DayOfMonth: int implements HasLabel
{
    use ParsesEnum;

    case First = 1;
    case Last = -1;
    case Second = 2;
    case Third = 3;
    case Fourth = 4;
    case Fifth = 5;
    case Sixth = 6;
    case Seventh = 7;
    case Eighth = 8;
    case Ninth = 9;
    case Tenth = 10;
    case Eleventh = 11;
    case Twelfth = 12;
    case Thirteenth = 13;
    case Fourteenth = 14;
    case Fifteenth = 15;
    case Sixteenth = 16;
    case Seventeenth = 17;
    case Eighteenth = 18;
    case Nineteenth = 19;
    case Twentieth = 20;
    case TwentyFirst = 21;
    case TwentySecond = 22;
    case TwentyThird = 23;
    case TwentyFourth = 24;
    case TwentyFifth = 25;
    case TwentySixth = 26;
    case TwentySeventh = 27;
    case TwentyEighth = 28;
    case TwentyNinth = 29;
    case Thirtieth = 30;
    case ThirtyFirst = 31;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::First => 'First',
            self::Last => 'Last',
            self::Second => '2nd',
            self::Third => '3rd',
            self::Fourth => '4th',
            self::Fifth => '5th',
            self::Sixth => '6th',
            self::Seventh => '7th',
            self::Eighth => '8th',
            self::Ninth => '9th',
            self::Tenth => '10th',
            self::Eleventh => '11th',
            self::Twelfth => '12th',
            self::Thirteenth => '13th',
            self::Fourteenth => '14th',
            self::Fifteenth => '15th',
            self::Sixteenth => '16th',
            self::Seventeenth => '17th',
            self::Eighteenth => '18th',
            self::Nineteenth => '19th',
            self::Twentieth => '20th',
            self::TwentyFirst => '21st',
            self::TwentySecond => '22nd',
            self::TwentyThird => '23rd',
            self::TwentyFourth => '24th',
            self::TwentyFifth => '25th',
            self::TwentySixth => '26th',
            self::TwentySeventh => '27th',
            self::TwentyEighth => '28th',
            self::TwentyNinth => '29th',
            self::Thirtieth => '30th',
            self::ThirtyFirst => '31st',
        };
    }

    public function isFirst(): bool
    {
        return $this === self::First;
    }

    public function isLast(): bool
    {
        return $this === self::Last;
    }

    public function resolveDate(Carbon | CarbonImmutable $date): Carbon | CarbonImmutable
    {
        if ($this->isLast()) {
            return $date->endOfMonth();
        }

        return $date->day(min($this->value, $date->daysInMonth));
    }

    public function mayExceedMonthLength(): bool
    {
        return $this->value > 28;
    }
}
