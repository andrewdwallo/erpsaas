<?php

namespace App\Transformers;

use App\DTO\AccountDTO;
use App\DTO\ReportCategoryDTO;
use App\DTO\ReportTypeDTO;

class CashFlowStatementReportTransformer extends SummaryReportTransformer
{
    public function getPdfView(): string
    {
        return 'components.company.reports.cash-flow-statement-pdf';
    }

    public function getTitle(): string
    {
        return 'Cash Flow Statement';
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
                    'net_movement' => $accountCategory->summary->netMovement ?? '',
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
                        'net_movement' => $account->balance->netMovement ?? '',
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
                            'net_movement' => $account->balance->netMovement ?? '',
                            default => '',
                        };
                    }

                    return $row;
                }, $type->accounts ?? []);

                // Subcategory (type) summary
                $typeSummary = [];
                foreach ($this->getColumns() as $column) {
                    $typeSummary[$column->getName()] = match ($column->getName()) {
                        'account_name' => 'Total ' . $typeName,
                        'net_movement' => $type->summary->netMovement ?? '',
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
                    'net_movement' => $accountCategory->summary->netMovement ?? '',
                    default => '',
                };
            }

            $types = [];

            // Iterate through each account type and calculate type summaries
            foreach ($accountCategory->types as $typeName => $type) {
                $typeSummary = [];

                foreach ($columns as $column) {
                    $typeSummary[$column->getName()] = match ($column->getName()) {
                        'account_name' => $typeName,
                        'net_movement' => $type->summary->netMovement ?? '',
                        default => '',
                    };
                }

                $types[$typeName] = new ReportTypeDTO(
                    header: [],
                    data: [],
                    summary: $typeSummary,
                );
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
                'label' => 'Total Cash Inflows',
                'value' => $this->report->overallTotal->debitBalance ?? '',
            ],
            [
                'label' => 'Total Cash Outflows',
                'value' => $this->report->overallTotal->creditBalance ?? '',
            ],
            [
                'label' => 'Net Cash Flow',
                'value' => $this->report->overallTotal->netMovement ?? '',
            ],
        ];
    }

    public function getOverviewAlignedWithColumns(): array
    {
        $summary = [];

        foreach ($this->getSummary() as $summaryItem) {
            $row = [];

            foreach ($this->getColumns() as $column) {
                $row[$column->getName()] = match ($column->getName()) {
                    'account_name' => $summaryItem['label'] ?? '',
                    'net_movement' => $summaryItem['value'] ?? '',
                    default => '',
                };
            }

            $summary[] = $row;
        }

        return $summary;
    }

    public function getSummaryOverviewAlignedWithColumns(): array
    {
        return array_map(static function ($row) {
            unset($row['account_code']);

            return $row;
        }, $this->getOverviewAlignedWithColumns());
    }

    public function getOverviewHeaders(): array
    {
        return once(function (): array {
            $headers = [];

            foreach ($this->getColumns() as $column) {
                $headers[$column->getName()] = $column->getName() === 'account_name' ? 'OVERVIEW' : '';
            }

            return $headers;
        });
    }

    public function getSummaryOverviewHeaders(): array
    {
        return once(function (): array {
            $headers = $this->getOverviewHeaders();

            unset($headers['account_code']);

            return $headers;
        });
    }

    public function getOverview(): array
    {
        $categories = [];

        foreach ($this->report->overview->categories as $categoryName => $category) {
            $header = [];

            foreach ($this->getColumns() as $column) {
                $header[$column->getName()] = $column->getName() === 'account_name' ? $categoryName : '';
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
                        'net_movement' => $account->balance->startingBalance ?? $account->balance->endingBalance ?? '',
                        default => '',
                    };
                }

                return $row;
            }, $category->accounts);

            $summary = [];

            foreach ($this->getColumns() as $column) {
                $summary[$column->getName()] = match ($column->getName()) {
                    'account_name' => 'Total ' . $categoryName,
                    'net_movement' => $category->summary->startingBalance ?? $category->summary->endingBalance ?? '',
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

    public function getSummaryOverview(): array
    {
        $summaryCategories = [];

        $columns = $this->getSummaryColumns();

        foreach ($this->report->overview->categories as $categoryName => $category) {
            $categorySummary = [];

            foreach ($columns as $column) {
                $categorySummary[$column->getName()] = match ($column->getName()) {
                    'account_name' => $categoryName,
                    'net_movement' => $category->summary->startingBalance ?? $category->summary->endingBalance ?? '',
                    default => '',
                };
            }

            $summaryCategories[] = new ReportCategoryDTO(
                header: [],
                data: [],
                summary: $categorySummary,
            );
        }

        return $summaryCategories;
    }
}
