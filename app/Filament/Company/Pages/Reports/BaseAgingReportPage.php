<?php

namespace App\Filament\Company\Pages\Reports;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Enums\Accounting\DocumentEntityType;
use App\Filament\Forms\Components\DateRangeSelect;
use App\Services\ExportService;
use App\Services\ReportService;
use App\Support\Column;
use App\Transformers\AgingReportTransformer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseAgingReportPage extends BaseReportPage
{
    protected static string $view = 'filament.company.pages.reports.trial-balance';

    protected ReportService $reportService;

    protected ExportService $exportService;

    abstract protected function getEntityType(): DocumentEntityType;

    public function boot(ReportService $reportService, ExportService $exportService): void
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    protected function initializeDefaultFilters(): void
    {
        if (empty($this->getFilterState('days_per_period'))) {
            $this->setFilterState('days_per_period', 30);
        }

        if (empty($this->getFilterState('number_of_periods'))) {
            $this->setFilterState('number_of_periods', 4);
        }
    }

    public function getTable(): array
    {
        $daysPerPeriod = $this->getFilterState('days_per_period');
        $numberOfPeriods = $this->getFilterState('number_of_periods');

        $columns = [
            Column::make('entity_name')
                ->label($this->getEntityType()->getLabel())
                ->alignment(Alignment::Left),
            Column::make('current')
                ->label('Current')
                ->alignment(Alignment::Right),
        ];

        for ($i = 1; $i < $numberOfPeriods; $i++) {
            $start = ($i - 1) * $daysPerPeriod + 1;
            $end = $i * $daysPerPeriod;

            $columns[] = Column::make("period_{$i}")
                ->label("{$start} to {$end}")
                ->alignment(Alignment::Right);
        }

        $columns[] = Column::make('over_periods')
            ->label('Over ' . (($numberOfPeriods - 1) * $daysPerPeriod))
            ->alignment(Alignment::Right);

        $columns[] = Column::make('total')
            ->label('Total')
            ->alignment(Alignment::Right);

        return $columns;
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                DateRangeSelect::make('dateRange')
                    ->label('As of')
                    ->selectablePlaceholder(false)
                    ->endDateField('asOfDate'),
                $this->getAsOfDateFormComponent(),
                TextInput::make('days_per_period')
                    ->label('Days per period')
                    ->integer()
                    ->mask(RawJs::make(<<<'JS'
                        $input > 365 ? '365' : '999'
                    JS)),
                TextInput::make('number_of_periods')
                    ->label('Number of periods')
                    ->integer()
                    ->mask(RawJs::make(<<<'JS'
                        $input > 10 ? '10' : '99'
                    JS)),
            ]);
    }

    protected function buildReport(array $columns): ReportDTO
    {
        return $this->reportService->buildAgingReport(
            $this->getFormattedAsOfDate(),
            $this->getEntityType(),
            $columns,
            $this->getFilterState('days_per_period'),
            $this->getFilterState('number_of_periods')
        );
    }

    protected function getTransformer(ReportDTO $reportDTO): ExportableReport
    {
        return new AgingReportTransformer($reportDTO, $this->getEntityType());
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->exportService->exportToCsv(
            $this->company,
            $this->report,
            endDate: $this->getFilterState('asOfDate')
        );
    }

    public function exportPDF(): StreamedResponse
    {
        return $this->exportService->exportToPdf(
            $this->company,
            $this->report,
            endDate: $this->getFilterState('asOfDate')
        );
    }
}
