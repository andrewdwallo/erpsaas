<?php

namespace App\Transformers;

use App\DTO\AccountTransactionDTO;
use App\DTO\ReportCategoryDTO;

class AccountTransactionReportTransformer extends BaseReportTransformer
{
    public function getPdfView(): string
    {
        return 'components.company.reports.account-transactions-report-pdf';
    }

    public function getTitle(): string
    {
        return 'Account Transactions';
    }

    /**
     * @return ReportCategoryDTO[]
     */
    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->report->categories as $categoryData) {
            $header = [];

            foreach ($this->getColumns() as $column) {
                if ($column->getName() === 'date') {
                    $header[0][$column->getName()] = $categoryData['category'];
                    $header[1][$column->getName()] = $categoryData['under'];
                }
            }

            // Map transaction data
            $data = array_map(function (AccountTransactionDTO $transaction) {
                $row = [];

                foreach ($this->getColumns() as $column) {
                    $row[$column->getName()] = match ($column->getName()) {
                        'date' => $transaction->date,
                        'description' => [
                            'id' => $transaction->id,
                            'description' => $transaction->description,
                            'tableAction' => $transaction->tableAction,
                        ],
                        'debit' => $transaction->debit,
                        'credit' => $transaction->credit,
                        'balance' => $transaction->balance,
                        default => '',
                    };
                }

                return $row;
            }, $categoryData['transactions']);

            $categories[] = new ReportCategoryDTO(
                header: $header,
                data: $data,
            );
        }

        return $categories;
    }

    public function getOverallTotals(): array
    {
        return [];
    }
}
