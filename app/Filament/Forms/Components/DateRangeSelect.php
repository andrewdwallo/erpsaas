<?php

namespace App\Filament\Forms\Components;

use App\Services\DateRangeService;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;

class DateRangeSelect extends Select
{
    public string $fiscalYearStartDate;

    public string $fiscalYearEndDate;

    public ?string $startDateField = null;

    public ?string $endDateField = null;

    protected function setUp(): void
    {
        parent::setUp();

        $company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $company->locale->fiscalYearEndDate();

        $this->options(app(DateRangeService::class)->getDateRangeOptions())
            ->live()
            ->afterStateUpdated(function ($state, Set $set) {
                $this->updateDateRange($state, $set);
            });
    }

    public function startDateField(string $fieldName): static
    {
        $this->startDateField = $fieldName;

        return $this;
    }

    public function endDateField(string $fieldName): static
    {
        $this->endDateField = $fieldName;

        return $this;
    }

    public function getStartDateField(): ?string
    {
        return $this->startDateField;
    }

    public function getEndDateField(): ?string
    {
        return $this->endDateField;
    }

    public function updateDateRange($state, Set $set): void
    {
        if ($state === null) {
            if ($this->startDateField) {
                $set($this->startDateField, null);
            }

            if ($this->endDateField) {
                $set($this->endDateField, null);
            }

            return;
        }

        [$type, $param1, $param2] = explode('-', $state) + [null, null, null];
        $this->processDateRange($type, $param1, $param2, $set);
    }

    public function processDateRange($type, $param1, $param2, Set $set): void
    {
        match ($type) {
            'FY' => $this->processFiscalYear($param1, $set),
            'FQ' => $this->processFiscalQuarter($param1, $param2, $set),
            'Y' => $this->processCalendarYear($param1, $set),
            'Q' => $this->processCalendarQuarter($param1, $param2, $set),
            'M' => $this->processMonth("{$param1}-{$param2}", $set),
            'Custom' => null,
        };
    }

    public function processFiscalYear($year, Set $set): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate)->subYears($diff);
        $fiscalYearEnd = Carbon::parse($this->fiscalYearEndDate)->subYears($diff);
        $this->setDateRange($fiscalYearStart, $fiscalYearEnd, $set);
    }

    public function processFiscalQuarter($quarter, $year, Set $set): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate)->subYears($diff);
        $quarterStart = $fiscalYearStart->copy()->addMonths(($quarter - 1) * 3);
        $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();
        $this->setDateRange($quarterStart, $quarterEnd, $set);
    }

    public function processCalendarYear($year, Set $set): void
    {
        $start = Carbon::createFromDate($year)->startOfYear();
        $end = Carbon::createFromDate($year)->endOfYear();
        $this->setDateRange($start, $end, $set);
    }

    public function processCalendarQuarter($quarter, $year, Set $set): void
    {
        $month = ($quarter - 1) * 3 + 1;
        $start = Carbon::createFromDate($year, $month, 1);
        $end = Carbon::createFromDate($year, $month, 1)->endOfQuarter();
        $this->setDateRange($start, $end, $set);
    }

    public function processMonth($yearMonth, Set $set): void
    {
        $start = Carbon::parse($yearMonth)->startOfMonth();
        $end = Carbon::parse($yearMonth)->endOfMonth();
        $this->setDateRange($start, $end, $set);
    }

    public function setDateRange(Carbon $start, Carbon $end, Set $set): void
    {
        if ($this->startDateField) {
            $set($this->startDateField, $start->startOfDay()->toDateTimeString());
        }

        if ($this->endDateField) {
            $set($this->endDateField, $end->isFuture() ? now()->endOfDay()->toDateTimeString() : $end->endOfDay()->toDateTimeString());
        }
    }
}
