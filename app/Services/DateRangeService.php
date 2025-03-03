<?php

namespace App\Services;

use App\Facades\Accounting;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class DateRangeService
{
    protected string $fiscalYearStartDate = '';

    protected string $fiscalYearEndDate = '';

    public function __construct()
    {
        $company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $company->locale->fiscalYearEndDate();
    }

    public function getDateRangeOptions(): array
    {
        return once(function () {
            return $this->generateDateRangeOptions();
        });
    }

    private function generateDateRangeOptions(): array
    {
        $earliestDate = Carbon::parse(Accounting::getEarliestTransactionDate());
        $currentDate = now();
        $fiscalYearStartCurrent = Carbon::parse($this->fiscalYearStartDate);

        $options = [
            'Fiscal Year' => [],
            'Fiscal Quarter' => [],
            'Calendar Year' => [],
            'Calendar Quarter' => [],
            'Month' => [],
            'Custom' => [],
        ];

        $period = CarbonPeriod::create($earliestDate, '1 month', $currentDate);

        foreach ($period as $date) {
            $options['Fiscal Year']['FY-' . $date->year] = $date->year;

            $fiscalYearStart = $fiscalYearStartCurrent->copy()->subYears($currentDate->year - $date->year);

            for ($i = 0; $i < 4; $i++) {
                $quarterNumber = $i + 1;
                $quarterStart = $fiscalYearStart->copy()->addMonths(($quarterNumber - 1) * 3);
                $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();

                if ($quarterStart->lessThanOrEqualTo($currentDate) && $quarterEnd->greaterThanOrEqualTo($earliestDate)) {
                    $options['Fiscal Quarter']['FQ-' . $quarterNumber . '-' . $date->year] = 'Q' . $quarterNumber . ' ' . $date->year;
                }
            }

            $options['Calendar Year']['Y-' . $date->year] = $date->year;
            $quarterKey = 'Q-' . $date->quarter . '-' . $date->year;
            $options['Calendar Quarter'][$quarterKey] = 'Q' . $date->quarter . ' ' . $date->year;
            $options['Month']['M-' . $date->format('Y-m')] = $date->format('F Y');
            $options['Custom']['Custom'] = 'Custom';
        }

        $options['Fiscal Year'] = array_reverse($options['Fiscal Year'], true);
        $options['Fiscal Quarter'] = array_reverse($options['Fiscal Quarter'], true);
        $options['Calendar Year'] = array_reverse($options['Calendar Year'], true);
        $options['Calendar Quarter'] = array_reverse($options['Calendar Quarter'], true);
        $options['Month'] = array_reverse($options['Month'], true);

        return $options;
    }

    public function getMatchingDateRangeOption(Carbon $startDate, Carbon $endDate): string
    {
        $options = $this->getDateRangeOptions();

        foreach ($options as $type => $ranges) {
            foreach ($ranges as $key => $label) {
                [$expectedStart, $expectedEnd] = $this->getExpectedDateRange($type, $key);

                if ($expectedStart === null || $expectedEnd === null) {
                    continue;
                }

                $expectedEnd = $expectedEnd->isFuture() ? now()->startOfDay() : $expectedEnd;

                if ($startDate->eq($expectedStart) && $endDate->eq($expectedEnd)) {
                    return $key; // Return the matching range key (e.g., "FY-2024")
                }
            }
        }

        return 'Custom'; // Return "Custom" if no matching range is found
    }

    private function getExpectedDateRange(string $type, string $key): array
    {
        switch ($type) {
            case 'Fiscal Year':
                $year = (int) substr($key, 3);
                $start = Carbon::parse($this->fiscalYearStartDate)->subYears(now()->year - $year)->startOfDay();
                $end = Carbon::parse($this->fiscalYearEndDate)->subYears(now()->year - $year)->startOfDay();

                break;

            case 'Fiscal Quarter':
                [$quarter, $year] = explode('-', substr($key, 3));
                $start = Carbon::parse($this->fiscalYearStartDate)->subYears(now()->year - $year)->addMonths(($quarter - 1) * 3)->startOfDay();
                $end = $start->copy()->addMonths(3)->subDay()->startOfDay();

                break;

            case 'Calendar Year':
                $year = (int) substr($key, 2);
                $start = Carbon::createFromDate($year)->startOfYear()->startOfDay();
                $end = Carbon::createFromDate($year)->endOfYear()->startOfDay();

                break;

            case 'Calendar Quarter':
                [$quarter, $year] = explode('-', substr($key, 2));
                $month = ($quarter - 1) * 3 + 1;
                $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $end = $start->copy()->endOfQuarter()->startOfDay();

                break;

            case 'Month':
                $yearMonth = substr($key, 2);
                $start = Carbon::parse($yearMonth)->startOfMonth()->startOfDay();
                $end = Carbon::parse($yearMonth)->endOfMonth()->startOfDay();

                break;

            default:
                return [null, null];
        }

        return [$start, $end];
    }
}
