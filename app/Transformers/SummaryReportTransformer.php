<?php

namespace App\Transformers;

use App\Contracts\HasSummaryReport;
use App\Support\Column;

abstract class SummaryReportTransformer extends BaseReportTransformer implements HasSummaryReport
{
    /**
     * @return Column[]
     */
    public function getSummaryColumns(): array
    {
        return once(function (): array {
            return collect($this->getColumns())
                ->reject(fn (Column $column) => $column->getName() === 'account_code')
                ->toArray();
        });
    }

    public function getSummaryHeaders(): array
    {
        return once(function (): array {
            $headers = [];

            foreach ($this->getSummaryColumns() as $column) {
                $headers[$column->getName()] = $column->getLabel();
            }

            return $headers;
        });
    }

    public function getSummaryPdfView(): string
    {
        return 'components.company.reports.summary-report-pdf';
    }
}
