<?php

namespace App\Transformers;

use App\DTO\EntityReportDTO;
use App\DTO\ReportCategoryDTO;
use App\DTO\ReportDTO;
use App\Enums\Accounting\DocumentEntityType;

class AgingReportTransformer extends BaseReportTransformer
{
    public function __construct(
        ReportDTO $report,
        private readonly DocumentEntityType $entityType,
    ) {
        parent::__construct($report);
    }

    public function getTitle(): string
    {
        return $this->entityType->getAgingReportTitle();
    }

    /**
     * @return ReportCategoryDTO[]
     */
    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $category) {
            $data = array_map(function (EntityReportDTO $entity) {
                $row = [];

                foreach ($this->getColumns() as $column) {
                    $columnName = $column->getName();

                    $row[$columnName] = match ($columnName) {
                        'entity_name' => [
                            'name' => $entity->name,
                            'id' => $entity->id,
                        ],
                        'current' => $entity->aging->current,
                        'over_periods' => $entity->aging->overPeriods,
                        'total' => $entity->aging->total,
                        default => str_starts_with($columnName, 'period_')
                            ? $entity->aging->periods[$columnName] ?? null
                            : '',
                    };
                }

                return $row;
            }, $category);

            $categories[] = new ReportCategoryDTO(
                header: null,
                data: $data,
                summary: null,
            );
        }

        return $categories;
    }

    public function getOverallTotals(): array
    {
        $totals = [];

        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();

            $totals[$columnName] = match ($columnName) {
                'entity_name' => 'Total',
                'current' => $this->report->agingSummary->current,
                'over_periods' => $this->report->agingSummary->overPeriods,
                'total' => $this->report->agingSummary->total,
                default => str_starts_with($columnName, 'period_')
                    ? $this->report->agingSummary->periods[$columnName] ?? null
                    : '',
            };
        }

        return $totals;
    }
}
