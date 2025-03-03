<?php

namespace App\Support;

use App\Enums\Accounting\DayOfMonth;
use App\Enums\Accounting\DayOfWeek;
use App\Enums\Accounting\Frequency;
use App\Enums\Accounting\IntervalType;
use App\Enums\Accounting\Month;
use Carbon\CarbonImmutable;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;

class ScheduleHandler
{
    protected CarbonImmutable $today;

    protected Set $set;

    protected ?Get $get;

    public function __construct(Set $set, ?Get $get = null)
    {
        $this->today = today()->toImmutable();
        $this->set = $set;
        $this->get = $get;
    }

    protected function setMany(Set $set, array $values): void
    {
        foreach ($values as $key => $value) {
            $set($key, $value);
        }
    }

    public function handleFrequencyChange(mixed $state): void
    {
        $frequency = Frequency::parse($state);

        match (true) {
            $frequency->isDaily() => $this->handleDaily(),
            $frequency->isWeekly() => $this->handleWeekly(),
            $frequency->isMonthly() => $this->handleMonthly(),
            $frequency->isYearly() => $this->handleYearly(),
            $frequency->isCustom() => $this->handleCustom(),
            default => null,
        };
    }

    public function handleIntervalTypeChange(mixed $state): void
    {
        $intervalType = IntervalType::parse($state);

        match (true) {
            $intervalType->isWeek() => $this->handleWeeklyInterval(),
            $intervalType->isMonth() => $this->handleMonthlyInterval(),
            $intervalType->isYear() => $this->handleYearlyInterval(),
            default => null,
        };
    }

    public function handleDateChange(?string $component, mixed $state): void
    {
        match ($component) {
            'start_date' => $this->syncComponentsToStartDate(Carbon::parse($state)),
            'month' => $this->handleMonthChange($state),
            'day_of_month' => $this->handleDayOfMonthChange($state),
            'day_of_week' => $this->handleDayOfWeekChange($state),
            default => null,
        };
    }

    protected function handleDaily(): void
    {
        $this->setMany($this->set, [
            'interval_value' => null,
            'interval_type' => null,
            'day_of_month' => null,
            'start_date' => $this->today->toDateString(),
        ]);
    }

    protected function handleWeekly(): void
    {
        $currentDayOfWeek = DayOfWeek::parse($this->today->dayOfWeek);

        $this->setMany($this->set, [
            'day_of_week' => $currentDayOfWeek,
            'start_date' => $this->today->toDateString(),
            'interval_value' => null,
            'interval_type' => null,
            'day_of_month' => null,
        ]);
    }

    protected function handleMonthly(): void
    {
        $dayOfMonth = DayOfMonth::First;
        $date = $dayOfMonth->resolveDate($this->today);

        $adjustedStartDate = $date->lt($this->today)
            ? $dayOfMonth->resolveDate($date->addMonth())
            : $dayOfMonth->resolveDate($date);

        $this->setMany($this->set, [
            'month' => null,
            'day_of_month' => $dayOfMonth,
            'start_date' => $adjustedStartDate->toDateString(),
            'interval_value' => null,
            'interval_type' => null,
        ]);
    }

    protected function handleYearly(): void
    {
        $currentMonth = Month::parse($this->today->month);
        $currentDayOfMonth = DayOfMonth::parse($this->today->day);

        $this->setMany($this->set, [
            'month' => $currentMonth,
            'day_of_month' => $currentDayOfMonth,
            'start_date' => $this->today->toDateString(),
            'interval_value' => null,
            'interval_type' => null,
        ]);
    }

    protected function handleCustom(): void
    {
        $dayOfMonth = DayOfMonth::First;
        $date = $dayOfMonth->resolveDate($this->today);

        $adjustedStartDate = $date->lt($this->today)
            ? $dayOfMonth->resolveDate($date->addMonth())
            : $dayOfMonth->resolveDate($date);

        $this->setMany($this->set, [
            'interval_value' => 1,
            'interval_type' => IntervalType::Month,
            'month' => null,
            'day_of_month' => $dayOfMonth,
            'start_date' => $adjustedStartDate->toDateString(),
        ]);
    }

    protected function handleWeeklyInterval(): void
    {
        $currentDayOfWeek = DayOfWeek::parse($this->today->dayOfWeek);

        $this->setMany($this->set, [
            'day_of_week' => $currentDayOfWeek,
            'start_date' => $this->today->toDateString(),
        ]);
    }

    protected function handleMonthlyInterval(): void
    {
        $dayOfMonth = DayOfMonth::First;
        $date = $dayOfMonth->resolveDate($this->today);

        $adjustedStartDate = $date->lt($this->today)
            ? $dayOfMonth->resolveDate($date->addMonth())
            : $dayOfMonth->resolveDate($date);

        $this->setMany($this->set, [
            'month' => null,
            'day_of_month' => $dayOfMonth,
            'start_date' => $adjustedStartDate->toDateString(),
        ]);
    }

    protected function handleYearlyInterval(): void
    {
        $currentMonth = Month::parse($this->today->month);
        $currentDayOfMonth = DayOfMonth::parse($this->today->day);

        $this->setMany($this->set, [
            'month' => $currentMonth,
            'day_of_month' => $currentDayOfMonth,
            'start_date' => $this->today->toDateString(),
        ]);
    }

    protected function syncComponentsToStartDate(Carbon $startDate): void
    {
        $frequency = Frequency::parse(($this->get)('frequency'));
        $intervalType = IntervalType::parse(($this->get)('interval_type'));

        if ($frequency->isWeekly() || $intervalType?->isWeek()) {
            ($this->set)('day_of_week', DayOfWeek::parse($startDate->dayOfWeek));
        }

        if ($frequency->isMonthly() || $intervalType?->isMonth() ||
            $frequency->isYearly() || $intervalType?->isYear()) {
            ($this->set)('day_of_month', $startDate->day);
        }

        if ($frequency->isYearly() || $intervalType?->isYear()) {
            ($this->set)('month', Month::parse($startDate->month));
        }
    }

    protected function handleMonthChange(mixed $state): void
    {
        if (! $this->get) {
            return;
        }

        $dayOfMonth = DayOfMonth::parse(($this->get)('day_of_month'));
        $frequency = Frequency::parse(($this->get)('frequency'));
        $intervalType = IntervalType::parse(($this->get)('interval_type'));
        $month = Month::parse($state);

        if (($frequency->isYearly() || $intervalType?->isYear()) && $month && $dayOfMonth) {
            $date = $dayOfMonth->resolveDate($this->today->month($month->value));

            $adjustedStartDate = $date->lt($this->today)
                ? $dayOfMonth->resolveDate($date->addYear()->month($month->value))
                : $dayOfMonth->resolveDate($date->month($month->value));

            $adjustedDay = min($dayOfMonth->value, $adjustedStartDate->daysInMonth);

            $this->setMany($this->set, [
                'day_of_month' => $adjustedDay,
                'start_date' => $adjustedStartDate->toDateString(),
            ]);
        }
    }

    protected function handleDayOfMonthChange(mixed $state): void
    {
        if (! $this->get) {
            return;
        }

        $dayOfMonth = DayOfMonth::parse($state);
        $frequency = Frequency::parse(($this->get)('frequency'));
        $intervalType = IntervalType::parse(($this->get)('interval_type'));
        $month = Month::parse(($this->get)('month'));

        if (($frequency->isMonthly() || $intervalType?->isMonth()) && $dayOfMonth) {
            $date = $dayOfMonth->resolveDate($this->today);

            $adjustedStartDate = $date->lt($this->today)
                ? $dayOfMonth->resolveDate($date->addMonth())
                : $dayOfMonth->resolveDate($date);

            ($this->set)('start_date', $adjustedStartDate->toDateString());
        }

        if (($frequency->isYearly() || $intervalType?->isYear()) && $month && $dayOfMonth) {
            $date = $dayOfMonth->resolveDate($this->today->month($month->value));

            $adjustedStartDate = $date->lt($this->today)
                ? $dayOfMonth->resolveDate($date->addYear()->month($month->value))
                : $dayOfMonth->resolveDate($date->month($month->value));

            ($this->set)('start_date', $adjustedStartDate->toDateString());
        }
    }

    protected function handleDayOfWeekChange(mixed $state): void
    {
        $dayOfWeek = DayOfWeek::parse($state);

        $adjustedStartDate = $this->today->is($dayOfWeek->name)
            ? $this->today
            : $this->today->next($dayOfWeek->name);

        ($this->set)('start_date', $adjustedStartDate->toDateString());
    }
}
