<?php

namespace App\Factories;

use App\Models\Company;
use Illuminate\Support\Carbon;

class ReportDateFactory
{
    public Carbon $fiscalYearStartDate;

    public Carbon $fiscalYearEndDate;

    public string $defaultDateRange;

    public Carbon $defaultStartDate;

    public Carbon $defaultEndDate;

    public Carbon $earliestTransactionDate;

    protected Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->buildReportDates();
    }

    protected function buildReportDates(): void
    {
        $fiscalYearStartDate = Carbon::parse($this->company->locale->fiscalYearStartDate())->startOfDay();
        $fiscalYearEndDate = Carbon::parse($this->company->locale->fiscalYearEndDate())->endOfDay();
        $defaultDateRange = 'FY-' . now()->year;
        $defaultStartDate = $fiscalYearStartDate->startOfDay();
        $defaultEndDate = $fiscalYearEndDate->isFuture() ? now()->endOfDay() : $fiscalYearEndDate->endOfDay();

        // Calculate the earliest transaction date based on the company's transactions
        $earliestTransactionDate = $this->company->transactions()->min('posted_at')
            ? Carbon::parse($this->company->transactions()->min('posted_at'))->startOfDay()
            : $defaultStartDate;

        // Assign values to properties
        $this->fiscalYearStartDate = $fiscalYearStartDate;
        $this->fiscalYearEndDate = $fiscalYearEndDate;
        $this->defaultDateRange = $defaultDateRange;
        $this->defaultStartDate = $defaultStartDate;
        $this->defaultEndDate = $defaultEndDate;
        $this->earliestTransactionDate = $earliestTransactionDate;
    }

    public function refresh(): self
    {
        $this->buildReportDates();

        return $this;
    }

    public static function create(Company $company): self
    {
        return new static($company);
    }
}
