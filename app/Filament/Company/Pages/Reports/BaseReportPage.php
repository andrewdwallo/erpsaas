<?php

namespace App\Filament\Company\Pages\Reports;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Filament\Company\Pages\Concerns\HasDeferredFiltersForm;
use App\Filament\Company\Pages\Concerns\HasTableColumnToggleForm;
use App\Filament\Company\Pages\Reports;
use App\Filament\Forms\Components\DateRangeSelect;
use App\Models\Company;
use App\Services\DateRangeService;
use App\Support\Column;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseReportPage extends Page
{
    use HasDeferredFiltersForm;
    use HasTableColumnToggleForm;

    public string $fiscalYearStartDate;

    public string $fiscalYearEndDate;

    public Company $company;

    public bool $reportLoaded = false;

    abstract protected function buildReport(array $columns): ReportDTO;

    abstract public function exportCSV(): StreamedResponse;

    abstract public function exportPDF(): StreamedResponse;

    abstract protected function getTransformer(ReportDTO $reportDTO): ExportableReport;

    /**
     * @return array<Column>
     */
    abstract public function getTable(): array;

    public function mount(): void
    {
        $this->initializeProperties();

        $this->loadDefaultDateRange();
    }

    protected function initializeProperties(): void
    {
        $this->company = auth()->user()->currentCompany;
        $this->fiscalYearStartDate = $this->company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $this->company->locale->fiscalYearEndDate();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getSlug(): string
    {
        $prefix = Reports::getSlug() . '/';

        if (filled(static::$slug)) {
            return $prefix . static::$slug;
        }

        return $prefix . str(class_basename(static::class))
            ->kebab()
            ->slug();
    }

    public function getBreadcrumbs(): array
    {
        return [
            Reports::getUrl() => Reports::getNavigationLabel(),
        ];
    }

    protected function loadDefaultDateRange(): void
    {
        $flatFields = $this->getFiltersForm()->getFlatFields();

        $dateRangeField = Arr::first($flatFields, static fn ($field) => $field instanceof DateRangeSelect);

        if (! $dateRangeField) {
            return;
        }

        $startDateField = $dateRangeField->getStartDateField();
        $endDateField = $dateRangeField->getEndDateField();

        $startDate = $startDateField ? $this->getFilterState($startDateField) : null;
        $endDate = $endDateField ? $this->getFilterState($endDateField) : null;

        $startDateCarbon = $this->isValidDate($startDate) ? Carbon::parse($startDate) : null;
        $endDateCarbon = $this->isValidDate($endDate) ? Carbon::parse($endDate) : null;

        if ($startDateCarbon && $endDateCarbon) {
            $this->setMatchingDateRange($startDateCarbon, $endDateCarbon);

            return;
        }

        if ($endDateCarbon && ! $startDateField) {
            $this->setAsOfDateRange($endDateCarbon);

            return;
        }

        if ($endDateField && ! $startDateField) {
            $this->setFilterState('dateRange', $this->getDefaultDateRange());
            $defaultEndDate = Carbon::parse($this->fiscalYearEndDate);
            $this->setFilterState($endDateField, $defaultEndDate->isFuture() ? now()->endOfDay()->toDateTimeString() : $defaultEndDate->endOfDay()->toDateTimeString());

            return;
        }

        if ($startDateField && $endDateField) {
            $this->setFilterState('dateRange', $this->getDefaultDateRange());
            $defaultStartDate = Carbon::parse($this->fiscalYearStartDate);
            $defaultEndDate = Carbon::parse($this->fiscalYearEndDate);
            $this->setDateRange($defaultStartDate, $defaultEndDate);
        }
    }

    protected function setMatchingDateRange($startDate, $endDate): void
    {
        $matchingDateRange = app(DateRangeService::class)->getMatchingDateRangeOption($startDate, $endDate);
        $this->setFilterState('dateRange', $matchingDateRange);
    }

    protected function setAsOfDateRange($endDate): void
    {
        $fiscalYearStart = Carbon::parse($this->fiscalYearStartDate);
        $asOfStartDate = $endDate->copy()->setMonth($fiscalYearStart->month)->setDay($fiscalYearStart->day);

        $this->setMatchingDateRange($asOfStartDate, $endDate);
    }

    public function loadReportData(): void
    {
        unset($this->report);

        $this->reportLoaded = true;
    }

    public function getDefaultDateRange(): string
    {
        return 'FY-' . now()->year;
    }

    #[Computed(persist: true)]
    public function report(): ?ExportableReport
    {
        if ($this->reportLoaded === false) {
            return null;
        }

        $columns = $this->getToggledColumns();
        $reportDTO = $this->buildReport($columns);

        return $this->getTransformer($reportDTO);
    }

    public function setDateRange(Carbon $start, Carbon $end): void
    {
        $this->setFilterState('startDate', $start->startOfDay()->toDateTimeString());
        $this->setFilterState('endDate', $end->isFuture() ? now()->endOfDay()->toDateTimeString() : $end->endOfDay()->toDateTimeString());
    }

    public function getFormattedStartDate(): string
    {
        return Carbon::parse($this->getFilterState('startDate'))->startOfDay()->toDateTimeString();
    }

    public function getFormattedEndDate(): string
    {
        return Carbon::parse($this->getFilterState('endDate'))->endOfDay()->toDateTimeString();
    }

    public function getFormattedAsOfDate(): string
    {
        return Carbon::parse($this->getFilterState('asOfDate'))->endOfDay()->toDateTimeString();
    }

    public function getDisplayAsOfDate(): string
    {
        return Carbon::parse($this->getFilterState('asOfDate'))->toDefaultDateFormat();
    }

    public function getDisplayStartDate(): string
    {
        return Carbon::parse($this->getFilterState('startDate'))->toDefaultDateFormat();
    }

    public function getDisplayEndDate(): string
    {
        return Carbon::parse($this->getFilterState('endDate'))->toDefaultDateFormat();
    }

    public function getDisplayDateRange(): string
    {
        $startDate = Carbon::parse($this->getFilterState('startDate'));
        $endDate = Carbon::parse($this->getFilterState('endDate'));

        return $startDate->toDefaultDateFormat() . ' - ' . $endDate->toDefaultDateFormat();
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

    protected function getDateRangeFormComponent(): DateRangeSelect
    {
        return DateRangeSelect::make('dateRange')
            ->label('Date range')
            ->selectablePlaceholder(false)
            ->startDateField('startDate')
            ->endDateField('endDate');
    }

    protected function getStartDateFormComponent(): DatePicker
    {
        return DatePicker::make('startDate')
            ->label('Start date')
            ->live()
            ->afterStateUpdated(static function ($state, Set $set) {
                $set('dateRange', 'Custom');
            });
    }

    protected function getEndDateFormComponent(): DatePicker
    {
        return DatePicker::make('endDate')
            ->label('End date')
            ->live()
            ->afterStateUpdated(static function (Set $set) {
                $set('dateRange', 'Custom');
            });
    }

    protected function getAsOfDateFormComponent(): DatePicker
    {
        return DatePicker::make('asOfDate')
            ->label('As of date')
            ->live()
            ->afterStateUpdated(static function (Set $set) {
                $set('dateRange', 'Custom');
            })
            ->extraFieldWrapperAttributes([
                'class' => 'report-hidden-label',
            ]);
    }
}
