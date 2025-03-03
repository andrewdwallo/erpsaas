<?php

namespace App\Transformers;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Support\Column;
use Filament\Support\Enums\Alignment;

abstract class BaseReportTransformer implements ExportableReport
{
    protected ReportDTO $report;

    public function __construct(ReportDTO $report)
    {
        $this->report = $report;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return once(function (): array {
            return $this->report->fields;
        });
    }

    public function getHeaders(): array
    {
        return once(function (): array {
            $headers = [];

            foreach ($this->getColumns() as $column) {
                $headers[$column->getName()] = $column->getLabel();
            }

            return $headers;
        });
    }

    public function getPdfView(): string
    {
        return 'components.company.reports.report-pdf';
    }

    public function getAlignment(int $index): string
    {
        $column = $this->getColumns()[$index];

        if ($column->getAlignment() === Alignment::Right) {
            return 'right';
        }

        if ($column->getAlignment() === Alignment::Center) {
            return 'center';
        }

        return 'left';
    }

    public function getAlignmentClass(string $columnName): string
    {
        return once(function () use ($columnName): string {
            /** @var Column|null $column */
            $column = collect($this->getColumns())->first(fn (Column $column) => $column->getName() === $columnName);

            if ($column?->getAlignment() === Alignment::Right) {
                return 'text-right';
            }

            if ($column?->getAlignment() === Alignment::Center) {
                return 'text-center';
            }

            return 'text-left';
        });
    }

    public function getStartDate(): ?string
    {
        return $this->report->startDate?->toDefaultDateFormat();
    }

    public function getEndDate(): ?string
    {
        return $this->report->endDate?->toDefaultDateFormat();
    }
}
