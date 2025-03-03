<?php

namespace App\Transformers;

use App\DTO\AccountDTO;
use App\DTO\ReportCategoryDTO;
use App\DTO\ReportDTO;
use App\Utilities\Currency\CurrencyAccessor;

class IncomeStatementReportTransformer extends SummaryReportTransformer
{
    protected string $totalRevenue;

    protected string $totalCogs;

    protected string $totalExpenses;

    public function __construct(ReportDTO $report)
    {
        parent::__construct($report);

        $this->calculateTotals();
    }

    public function getSummaryPdfView(): string
    {
        return 'components.company.reports.income-statement-summary-pdf';
    }

    public function getTitle(): string
    {
        return 'Income Statement';
    }

    public function calculateTotals(): void
    {
        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            match ($accountCategoryName) {
                'Revenue' => $this->totalRevenue = $accountCategory->summary->netMovement ?? '',
                'Cost of Goods Sold' => $this->totalCogs = $accountCategory->summary->netMovement ?? '',
                'Expenses' => $this->totalExpenses = $accountCategory->summary->netMovement ?? '',
            };
        }
    }

    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            $header = [];

            foreach ($this->getColumns() as $column) {
                $header[$column->getName()] = $column->getName() === 'account_name' ? $accountCategoryName : '';
            }

            $data = array_map(function (AccountDTO $account) {
                $row = [];

                foreach ($this->getColumns() as $column) {
                    $row[$column->getName()] = match ($column->getName()) {
                        'account_code' => $account->accountCode,
                        'account_name' => [
                            'name' => $account->accountName,
                            'id' => $account->accountId ?? null,
                            'start_date' => $account->startDate,
                            'end_date' => $account->endDate,
                        ],
                        'net_movement' => $account->balance->netMovement ?? '',
                        default => '',
                    };
                }

                return $row;
            }, $accountCategory->accounts);

            $summary = [];

            foreach ($this->getColumns() as $column) {
                $summary[$column->getName()] = match ($column->getName()) {
                    'account_name' => 'Total ' . $accountCategoryName,
                    'net_movement' => $accountCategory->summary->netMovement ?? '',
                    default => '',
                };
            }

            $categories[] = new ReportCategoryDTO(
                header: $header,
                data: $data,
                summary: $summary,
            );
        }

        return $categories;
    }

    public function getSummaryCategories(): array
    {
        $summaryCategories = [];

        $columns = $this->getSummaryColumns();

        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            // Category-level summary
            $categorySummary = [];
            foreach ($columns as $column) {
                $categorySummary[$column->getName()] = match ($column->getName()) {
                    'account_name' => $accountCategoryName,
                    'net_movement' => $accountCategory->summary->netMovement ?? '',
                    default => '',
                };
            }

            // Add the category summary to the final array
            $summaryCategories[$accountCategoryName] = new ReportCategoryDTO(
                header: [],
                data: [], // No direct accounts are needed here, only summaries
                summary: $categorySummary,
                types: [] // No types for the income statement
            );
        }

        return $summaryCategories;
    }

    public function getGrossProfit(): array
    {
        $grossProfit = [];

        $columns = $this->getSummaryColumns();

        $revenue = money($this->totalRevenue, CurrencyAccessor::getDefaultCurrency())->getAmount();
        $cogs = money($this->totalCogs, CurrencyAccessor::getDefaultCurrency())->getAmount();

        $grossProfitAmount = $revenue - $cogs;
        $grossProfitFormatted = money($grossProfitAmount, CurrencyAccessor::getDefaultCurrency(), true)->format();

        foreach ($columns as $column) {
            $grossProfit[$column->getName()] = match ($column->getName()) {
                'account_name' => 'Gross Profit',
                'net_movement' => $grossProfitFormatted,
                default => '',
            };
        }

        return $grossProfit;
    }

    public function getOverallTotals(): array
    {
        $totals = [];

        foreach ($this->getColumns() as $column) {
            $totals[$column->getName()] = match ($column->getName()) {
                'account_name' => 'Net Earnings',
                'net_movement' => $this->report->overallTotal->netMovement ?? '',
                default => '',
            };
        }

        return $totals;
    }

    public function getSummaryOverallTotals(): array
    {
        $totals = [];
        $columns = $this->getSummaryColumns();

        foreach ($columns as $column) {
            $totals[$column->getName()] = match ($column->getName()) {
                'account_name' => 'Net Earnings',
                'net_movement' => $this->report->overallTotal->netMovement ?? '',
                default => '',
            };
        }

        return $totals;
    }

    public function getSummary(): array
    {
        return [
            [
                'label' => 'Revenue',
                'value' => $this->totalRevenue,
            ],
            [
                'label' => 'Cost of Goods Sold',
                'value' => $this->totalCogs,
            ],
            [
                'label' => 'Expenses',
                'value' => $this->totalExpenses,
            ],
            [
                'label' => 'Net Earnings',
                'value' => $this->report->overallTotal->netMovement ?? '',
            ],
        ];
    }
}
