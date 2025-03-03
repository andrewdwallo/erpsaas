<?php

namespace App\Transformers;

use App\DTO\AccountDTO;
use App\DTO\ReportCategoryDTO;
use App\DTO\ReportDTO;
use App\DTO\ReportTypeDTO;
use App\Utilities\Currency\CurrencyAccessor;

class BalanceSheetReportTransformer extends SummaryReportTransformer
{
    protected string $totalAssets;

    protected string $totalLiabilities;

    protected string $totalEquity;

    public function __construct(ReportDTO $report)
    {
        parent::__construct($report);

        $this->calculateTotals();
    }

    public function getTitle(): string
    {
        return 'Balance Sheet';
    }

    public function calculateTotals(): void
    {
        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            match ($accountCategoryName) {
                'Assets' => $this->totalAssets = $accountCategory->summary->endingBalance ?? '',
                'Liabilities' => $this->totalLiabilities = $accountCategory->summary->endingBalance ?? '',
                'Equity' => $this->totalEquity = $accountCategory->summary->endingBalance ?? '',
            };
        }
    }

    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            // Header for the main category
            $header = [];

            foreach ($this->getColumns() as $column) {
                $header[$column->getName()] = $column->getName() === 'account_name' ? $accountCategoryName : '';
            }

            // Category-level summary
            $categorySummary = [];
            foreach ($this->getColumns() as $column) {
                $categorySummary[$column->getName()] = match ($column->getName()) {
                    'account_name' => 'Total ' . $accountCategoryName,
                    'ending_balance' => $accountCategory->summary->endingBalance ?? '',
                    default => '',
                };
            }

            // Accounts directly under the main category
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
                        'ending_balance' => $account->balance->endingBalance ?? '',
                        default => '',
                    };
                }

                return $row;
            }, $accountCategory->accounts ?? []);

            // Subcategories (types) under the main category
            $types = [];
            foreach ($accountCategory->types as $typeName => $type) {
                // Header for subcategory (type)
                $typeHeader = [];
                foreach ($this->getColumns() as $column) {
                    $typeHeader[$column->getName()] = $column->getName() === 'account_name' ? $typeName : '';
                }

                // Account data for the subcategory
                $typeData = array_map(function (AccountDTO $account) {
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
                            'ending_balance' => $account->balance->endingBalance ?? '',
                            default => '',
                        };
                    }

                    return $row;
                }, $type->accounts);

                // Subcategory (type) summary
                $typeSummary = [];
                foreach ($this->getColumns() as $column) {
                    $typeSummary[$column->getName()] = match ($column->getName()) {
                        'account_name' => 'Total ' . $typeName,
                        'ending_balance' => $type->summary->endingBalance ?? '',
                        default => '',
                    };
                }

                // Add subcategory (type) to the list
                $types[$typeName] = new ReportTypeDTO(
                    header: $typeHeader,
                    data: $typeData,
                    summary: $typeSummary,
                );
            }

            // Add the category to the final array with its direct accounts and subcategories (types)
            $categories[$accountCategoryName] = new ReportCategoryDTO(
                header: $header,
                data: $data, // Direct accounts under the category
                summary: $categorySummary,
                types: $types, // Subcategories (types) under the category
            );
        }

        return $categories;
    }

    public function getSummaryCategories(): array
    {
        $summaryCategories = [];

        $columns = $this->getSummaryColumns();

        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            $categoryHeader = [];

            foreach ($columns as $column) {
                $categoryHeader[$column->getName()] = $column->getName() === 'account_name' ? $accountCategoryName : '';
            }

            $categorySummary = [];
            foreach ($columns as $column) {
                $categorySummary[$column->getName()] = match ($column->getName()) {
                    'account_name' => 'Total ' . $accountCategoryName,
                    'ending_balance' => $accountCategory->summary->endingBalance ?? '',
                    default => '',
                };
            }

            $types = [];
            $totalTypeSummaries = 0;

            // Iterate through each account type and calculate type summaries
            foreach ($accountCategory->types as $typeName => $type) {
                $typeSummary = [];
                $typeEndingBalance = 0;

                foreach ($columns as $column) {
                    $typeSummary[$column->getName()] = match ($column->getName()) {
                        'account_name' => 'Total ' . $typeName,
                        'ending_balance' => $type->summary->endingBalance ?? '',
                        default => '',
                    };

                    if ($column->getName() === 'ending_balance') {
                        $typeEndingBalance = $type->summary->endingBalance ?? 0;
                    }
                }

                $typeEndingBalance = money($typeEndingBalance, CurrencyAccessor::getDefaultCurrency())->getAmount();

                $totalTypeSummaries += $typeEndingBalance;

                $types[$typeName] = new ReportTypeDTO(
                    header: [],
                    data: [],
                    summary: $typeSummary,
                );
            }

            // Only for the "Equity" category, calculate and add "Total Other Equity"
            if ($accountCategoryName === 'Equity') {
                $totalEquitySummary = $accountCategory->summary->endingBalance ?? 0;
                $totalEquitySummary = money($totalEquitySummary, CurrencyAccessor::getDefaultCurrency())->getAmount();
                $totalOtherEquity = $totalEquitySummary - $totalTypeSummaries;

                if ($totalOtherEquity != 0) {
                    $totalOtherEquityFormatted = money($totalOtherEquity, CurrencyAccessor::getDefaultCurrency(), true)->format();

                    // Add "Total Other Equity" as a new "type"
                    $otherEquitySummary = [];
                    foreach ($columns as $column) {
                        $otherEquitySummary[$column->getName()] = match ($column->getName()) {
                            'account_name' => 'Total Other Equity',
                            'ending_balance' => $totalOtherEquityFormatted,
                            default => '',
                        };
                    }

                    $types['Total Other Equity'] = new ReportTypeDTO(
                        header: [],
                        data: [],
                        summary: $otherEquitySummary,
                    );
                }
            }

            // Add the category with its types and summary to the final array
            $summaryCategories[$accountCategoryName] = new ReportCategoryDTO(
                header: $categoryHeader,
                data: [],
                summary: $categorySummary,
                types: $types,
            );
        }

        return $summaryCategories;
    }

    public function getOverallTotals(): array
    {
        return [];
    }

    public function getSummaryOverallTotals(): array
    {
        return [];
    }

    public function getSummary(): array
    {
        return [
            [
                'label' => 'Total Assets',
                'value' => $this->totalAssets,
            ],
            [
                'label' => 'Total Liabilities',
                'value' => $this->totalLiabilities,
            ],
            [
                'label' => 'Net Assets',
                'value' => $this->report->overallTotal->endingBalance ?? '',
            ],
        ];
    }
}
