<?php

namespace App\Filament\Company\Pages\Reports;

use App\DTO\AccountBalanceReportDTO;
use App\Models\Company;
use App\Services\AccountBalancesExportService;
use App\Services\AccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountBalances extends Page
{
    protected static string $view = 'filament.company.pages.reports.account-balances';

    protected static ?string $slug = 'reports/account-balances';

    public string $startDate = '';

    public string $endDate = '';

    public string $dateRange = '';

    public string $fiscalYearStartDate = '';

    public string $fiscalYearEndDate = '';

    public Company $company;

    public AccountBalanceReportDTO $accountBalanceReport;

    protected AccountService $accountService;

    protected AccountBalancesExportService $accountBalancesExportService;

    public function boot(AccountService $accountService, AccountBalancesExportService $accountBalancesExportService): void
    {
        $this->accountService = $accountService;
        $this->accountBalancesExportService = $accountBalancesExportService;
    }

    public function mount(): void
    {
        $this->company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $this->company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $this->company->locale->fiscalYearEndDate();
        $this->dateRange = $this->getDefaultDateRange();
        $this->updateDateRange($this->dateRange);

        $this->loadAccountBalances();
    }

    public function getDefaultDateRange(): string
    {
        return 'FY-' . now()->year;
    }

    public function loadAccountBalances(): void
    {
        $startTime = microtime(true);
        $this->accountBalanceReport = $this->accountService->buildAccountBalanceReport($this->startDate, $this->endDate);
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime);
        info('Account balance report loaded in ' . $executionTime . ' seconds');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportCSV')
                    ->label('CSV')
                    ->action(fn () => $this->exportCSV()),
                Action::make('exportPDF')
                    ->label('PDF')
                    ->action(fn () => $this->exportPDF()),
            ])
                ->label('Export')
                ->button()
                ->outlined()
                ->dropdownWidth('max-w-[7rem]')
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-c-chevron-down')
                ->iconSize(IconSize::Small)
                ->iconPosition(IconPosition::After),
        ];
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->accountBalancesExportService->exportToCsv($this->company, $this->accountBalanceReport, $this->startDate, $this->endDate);
    }

    public function exportPDF(): StreamedResponse
    {
        $pdf = Pdf::loadView('components.company.reports.account-balances', [
            'accountBalanceReport' => $this->accountBalanceReport,
            'startDate' => Carbon::parse($this->startDate)->format('M d, Y'),
            'endDate' => Carbon::parse($this->endDate)->format('M d, Y'),
        ])->setPaper('a4')->setOption(['defaultFont' => 'sans-serif', 'isPhpEnabled' => true]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'account-balances.pdf');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Select::make('dateRange')
                        ->label('Date Range')
                        ->options($this->getDateRangeOptions())
                        ->selectablePlaceholder(false)
                        ->afterStateUpdated(function ($state) {
                            $this->updateDateRange($state);
                        }),
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->displayFormat('Y-m-d')
                        ->afterStateUpdated(static function (Set $set) {
                            $set('dateRange', 'Custom');
                        }),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->displayFormat('Y-m-d')
                        ->afterStateUpdated(static function (Set $set) {
                            $set('dateRange', 'Custom');
                        }),
                ])->live(),
            ]);
    }

    public function getDateRangeOptions(): array
    {
        $earliestDate = Carbon::parse($this->accountService->getEarliestTransactionDate());
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

    public function updateDateRange($state): void
    {
        [$type, $param1, $param2] = explode('-', $state) + [null, null, null];
        $this->processDateRange($type, $param1, $param2);
    }

    public function processDateRange($type, $param1, $param2): void
    {
        match ($type) {
            'FY' => $this->processFiscalYear($param1),
            'FQ' => $this->processFiscalQuarter($param1, $param2),
            'Y' => $this->processCalendarYear($param1),
            'Q' => $this->processCalendarQuarter($param1, $param2),
            'M' => $this->processMonth("{$param1}-{$param2}"),
        };
    }

    public function processFiscalYear($year): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate)->subYears($diff);
        $fiscalYearEnd = Carbon::parse($this->fiscalYearEndDate)->subYears($diff);
        $this->setDateRange($fiscalYearStart, $fiscalYearEnd);
    }

    public function processFiscalQuarter($quarter, $year): void
    {
        $currentYear = now()->year;
        $diff = $currentYear - $year;
        $fiscalYearStart = Carbon::parse($this->company->locale->fiscal_year_start_date)->subYears($diff);
        $quarterStart = $fiscalYearStart->copy()->addMonths(($quarter - 1) * 3);
        $quarterEnd = $quarterStart->copy()->addMonths(3)->subDay();
        $this->setDateRange($quarterStart, $quarterEnd);
    }

    public function processCalendarYear($year): void
    {
        $start = Carbon::createFromDate($year)->startOfYear();
        $end = Carbon::createFromDate($year)->endOfYear();
        $this->setDateRange($start, $end);
    }

    public function processCalendarQuarter($quarter, $year): void
    {
        $month = ($quarter - 1) * 3 + 1;
        $start = Carbon::createFromDate($year, $month, 1);
        $end = Carbon::createFromDate($year, $month, 1)->endOfQuarter();
        $this->setDateRange($start, $end);
    }

    public function processMonth($yearMonth): void
    {
        $start = Carbon::parse($yearMonth)->startOfMonth();
        $end = Carbon::parse($yearMonth)->endOfMonth();
        $this->setDateRange($start, $end);
    }

    public function setDateRange(Carbon $start, Carbon $end): void
    {
        $this->startDate = $start->format('Y-m-d');
        $this->endDate = $end->isFuture() ? now()->format('Y-m-d') : $end->format('Y-m-d');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
