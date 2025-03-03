<?php

namespace App\Transformers;

use App\DTO\EntityReportDTO;
use App\DTO\ReportCategoryDTO;
use App\DTO\ReportDTO;
use App\Enums\Accounting\DocumentEntityType;

class EntityPaymentPerformanceReportTransformer extends BaseReportTransformer
{
    public function __construct(
        ReportDTO $report,
        private readonly DocumentEntityType $entityType,
    ) {
        parent::__construct($report);
    }

    public function getTitle(): string
    {
        return $this->entityType->getPaymentPerformanceReportTitle();
    }

    /**
     * @return ReportCategoryDTO[]
     */
    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $categoryName => $category) {
            $data = array_map(function (EntityReportDTO $entity) {
                $row = [];

                foreach ($this->getColumns() as $column) {
                    $row[$column->getName()] = match ($column->getName()) {
                        'entity_name' => [
                            'name' => $entity->name,
                            'id' => $entity->id,
                        ],
                        'total_documents' => $entity->paymentMetrics->totalDocuments,
                        'on_time_count' => $entity->paymentMetrics->onTimeCount,
                        'late_count' => $entity->paymentMetrics->lateCount,
                        'avg_days_to_pay' => $entity->paymentMetrics->avgDaysToPay,
                        'avg_days_late' => $entity->paymentMetrics->avgDaysLate,
                        'on_time_payment_rate' => $entity->paymentMetrics->onTimePaymentRate,
                        default => '',
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
            $totals[$column->getName()] = match ($column->getName()) {
                'entity_name' => 'Overall Totals',
                'total_documents' => $this->report->overallPaymentMetrics->totalDocuments,
                'on_time_count' => $this->report->overallPaymentMetrics->onTimeCount,
                'late_count' => $this->report->overallPaymentMetrics->lateCount,
                'avg_days_to_pay' => $this->report->overallPaymentMetrics->avgDaysToPay,
                'avg_days_late' => $this->report->overallPaymentMetrics->avgDaysLate,
                'on_time_payment_rate' => $this->report->overallPaymentMetrics->onTimePaymentRate,
                default => '',
            };
        }

        return $totals;
    }
}
