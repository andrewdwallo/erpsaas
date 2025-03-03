<?php

namespace App\Transformers;

use App\DTO\AccountDTO;
use App\DTO\ReportCategoryDTO;

class TrialBalanceReportTransformer extends BaseReportTransformer
{
    public function getTitle(): string
    {
        return match ($this->report->reportType) {
            'postClosing' => 'Post-Closing Trial Balance',
            default => 'Standard Trial Balance',
        };
    }

    /**
     * @return ReportCategoryDTO[]
     */
    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $accountCategoryName => $accountCategory) {
            // Initialize header with empty strings
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
                        'debit_balance' => $account->balance->debitBalance,
                        'credit_balance' => $account->balance->creditBalance,
                        default => '',
                    };
                }

                return $row;
            }, $accountCategory->accounts);

            $summary = [];

            foreach ($this->getColumns() as $column) {
                $summary[$column->getName()] = match ($column->getName()) {
                    'account_name' => 'Total ' . $accountCategoryName,
                    'debit_balance' => $accountCategory->summary->debitBalance,
                    'credit_balance' => $accountCategory->summary->creditBalance,
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

    public function getOverallTotals(): array
    {
        $totals = [];

        foreach ($this->getColumns() as $column) {
            $totals[$column->getName()] = match ($column->getName()) {
                'account_name' => 'Total for all accounts',
                'debit_balance' => $this->report->overallTotal->debitBalance,
                'credit_balance' => $this->report->overallTotal->creditBalance,
                default => '',
            };
        }

        return $totals;
    }
}
