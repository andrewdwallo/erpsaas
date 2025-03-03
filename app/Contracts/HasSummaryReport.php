<?php

namespace App\Contracts;

use App\DTO\ReportCategoryDTO;
use App\Support\Column;

interface HasSummaryReport
{
    /**
     * @return Column[]
     */
    public function getSummaryColumns(): array;

    public function getSummaryHeaders(): array;

    /**
     * @return ReportCategoryDTO[]
     */
    public function getSummaryCategories(): array;

    public function getSummaryOverallTotals(): array;

    public function getSummaryPdfView(): string;
}
