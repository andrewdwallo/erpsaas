<?php

namespace App\Services;

use App\Contracts\ExportableReport;
use App\Contracts\HasSummaryReport;
use App\Models\Company;
use App\Transformers\CashFlowStatementReportTransformer;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use League\Csv\Bom;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function exportToCsv(Company $company, ExportableReport $report, ?string $startDate = null, ?string $endDate = null, ?string $activeTab = null): StreamedResponse
    {
        if ($startDate && $endDate) {
            $formattedStartDate = Carbon::parse($startDate)->toDateString();
            $formattedEndDate = Carbon::parse($endDate)->toDateString();
            $dateLabel = $formattedStartDate . ' to ' . $formattedEndDate;
        } else {
            $formattedAsOfDate = Carbon::parse($endDate)->toDateString();
            $dateLabel = $formattedAsOfDate;
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

        $filename = $company->name . ' ' . $report->getTitle() . ' ' . $dateLabel . ' ' . $timestamp . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($startDate, $endDate, $report, $company, $activeTab) {
            $csv = Writer::createFromStream(fopen('php://output', 'wb'));
            $csv->setOutputBOM(Bom::Utf8);

            if ($startDate && $endDate) {
                $defaultStartDateFormat = Carbon::parse($startDate)->toDefaultDateFormat();
                $defaultEndDateFormat = Carbon::parse($endDate)->toDefaultDateFormat();
                $dateLabel = 'Date Range: ' . $defaultStartDateFormat . ' to ' . $defaultEndDateFormat;
            } else {
                $dateLabel = 'As of ' . Carbon::parse($endDate)->toDefaultDateFormat();
            }

            $csv->insertOne([$report->getTitle()]);
            $csv->insertOne([$company->name]);
            $csv->insertOne([$dateLabel]);
            $csv->insertOne([]);

            if ($activeTab === 'summary') {
                $this->writeSummaryTableToCsv($csv, $report);
            } else {
                $this->writeDetailedTableToCsv($csv, $report);
            }
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function writeSummaryTableToCsv(Writer $csv, ExportableReport $report): void
    {
        /** @var HasSummaryReport $report */
        $csv->insertOne($report->getSummaryHeaders());

        foreach ($report->getSummaryCategories() as $category) {
            if (filled($category->header)) {
                $csv->insertOne($category->header);
            }

            foreach ($category->types ?? [] as $type) {
                $csv->insertOne($type->summary);
            }

            if (filled($category->summary)) {
                $csv->insertOne($category->summary);
            }

            if ($category->summary['account_name'] === 'Cost of Goods Sold' && method_exists($report, 'getGrossProfit') && filled($report->getGrossProfit())) {
                $csv->insertOne($report->getGrossProfit());
            }

            if (filled($category->header)) {
                $csv->insertOne([]);
            }
        }

        if (method_exists($report, 'getSummaryOverviewHeaders') && filled($report->getSummaryOverviewHeaders())) {
            $this->writeSummaryOverviewTableToCsv($csv, $report);
        }

        if (filled($report->getSummaryOverallTotals())) {
            $csv->insertOne($report->getSummaryOverallTotals());
        }
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function writeDetailedTableToCsv(Writer $csv, ExportableReport $report): void
    {
        $csv->insertOne($report->getHeaders());

        foreach ($report->getCategories() as $category) {
            $this->writeDataRowsToCsv($csv, $category->header, $category->data, $report->getColumns());

            foreach ($category->types ?? [] as $type) {
                $this->writeDataRowsToCsv($csv, $type->header, $type->data, $report->getColumns());

                if (filled($type->summary)) {
                    $csv->insertOne($type->summary);
                }
            }

            if (filled($category->summary)) {
                $csv->insertOne($category->summary);

                $csv->insertOne([]);
            }
        }

        if (method_exists($report, 'getOverviewHeaders') && filled($report->getOverviewHeaders())) {
            $this->writeOverviewTableToCsv($csv, $report);
        }

        if (filled($report->getOverallTotals())) {
            $csv->insertOne($report->getOverallTotals());
        }
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function writeSummaryOverviewTableToCsv(Writer $csv, ExportableReport $report): void
    {
        /** @var CashFlowStatementReportTransformer $report */
        $headers = $report->getSummaryOverviewHeaders();

        if (filled($headers)) {
            $csv->insertOne($headers);
        }

        foreach ($report->getSummaryOverview() as $overviewCategory) {
            if (filled($overviewCategory->header)) {
                $this->writeDataRowsToCsv($csv, $overviewCategory->header, $overviewCategory->data, $report->getSummaryColumns());
            }

            if (filled($overviewCategory->summary)) {
                $csv->insertOne($overviewCategory->summary);
            }

            if ($overviewCategory->summary['account_name'] === 'Starting Balance') {
                foreach ($report->getSummaryOverviewAlignedWithColumns() as $summaryRow) {
                    $row = [];

                    foreach ($report->getSummaryColumns() as $column) {
                        $columnName = $column->getName();
                        $row[] = $summaryRow[$columnName] ?? '';
                    }

                    if (array_filter($row)) {
                        $csv->insertOne($row);
                    }
                }
            }
        }
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function writeOverviewTableToCsv(Writer $csv, ExportableReport $report): void
    {
        /** @var CashFlowStatementReportTransformer $report */
        $headers = $report->getOverviewHeaders();

        if (filled($headers)) {
            $csv->insertOne($headers);
        }

        foreach ($report->getOverview() as $overviewCategory) {
            if (filled($overviewCategory->header)) {
                $this->writeDataRowsToCsv($csv, $overviewCategory->header, $overviewCategory->data, $report->getColumns());
            }

            if (filled($overviewCategory->summary)) {
                $csv->insertOne($overviewCategory->summary);
            }

            if ($overviewCategory->header['account_name'] === 'Starting Balance') {
                foreach ($report->getOverviewAlignedWithColumns() as $summaryRow) {
                    $row = [];

                    foreach ($report->getColumns() as $column) {
                        $columnName = $column->getName();
                        $row[] = $summaryRow[$columnName] ?? '';
                    }

                    if (array_filter($row)) {
                        $csv->insertOne($row);
                    }
                }
            }
        }
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    protected function writeDataRowsToCsv(Writer $csv, ?array $header, array $data, array $columns): void
    {
        if ($header) {
            if (isset($header[0]) && is_array($header[0])) {
                foreach ($header as $headerRow) {
                    $csv->insertOne($headerRow);
                }
            } else {
                $csv->insertOne($header);
            }
        }

        // Output data rows
        foreach ($data as $rowData) {
            $row = [];

            foreach ($columns as $column) {
                $columnName = $column->getName();
                $cell = $rowData[$columnName] ?? '';

                if ($column->isDate()) {
                    try {
                        $row[] = Carbon::parse($cell)->toDateString();
                    } catch (InvalidFormatException) {
                        $row[] = $cell;
                    }
                } elseif (is_array($cell)) {
                    $row[] = $cell['name'] ?? $cell['description'] ?? '';
                } else {
                    $row[] = $cell;
                }
            }

            $csv->insertOne($row);
        }
    }

    public function exportToPdf(Company $company, ExportableReport $report, ?string $startDate = null, ?string $endDate = null, ?string $activeTab = null): StreamedResponse
    {
        if ($startDate && $endDate) {
            $formattedStartDate = Carbon::parse($startDate)->toDateString();
            $formattedEndDate = Carbon::parse($endDate)->toDateString();
            $dateLabel = $formattedStartDate . ' to ' . $formattedEndDate;
        } else {
            $formattedAsOfDate = Carbon::parse($endDate)->toDateString();
            $dateLabel = $formattedAsOfDate;
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

        $filename = $company->name . ' ' . $report->getTitle() . ' ' . $dateLabel . ' ' . $timestamp . '.pdf';

        $view = $activeTab === 'summary' ? $report->getSummaryPdfView() : $report->getPdfView();

        $pdf = SnappyPdf::loadView($view, [
            'company' => $company,
            'report' => $report,
            'startDate' => $startDate ? Carbon::parse($startDate)->toDefaultDateFormat() : null,
            'endDate' => $endDate ? Carbon::parse($endDate)->toDefaultDateFormat() : null,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->inline();
        }, $filename);
    }
}
