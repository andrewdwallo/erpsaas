<?php

namespace App\Services;

use App\DTO\AccountBalanceReportDTO;
use App\Models\Company;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountBalancesExportService
{
    public function exportToCsv(Company $company, AccountBalanceReportDTO $accountBalanceReport, string $startDate, string $endDate): StreamedResponse
    {
        // Construct the filename
        $filename = $company->name . ' Account Balances ' . $startDate . ' to ' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = static function () use ($company, $startDate, $endDate, $accountBalanceReport) {
            $file = fopen('php://output', 'wb');

            fputcsv($file, ['Account Balances']);
            fputcsv($file, [$company->name]);
            fputcsv($file, ['Date Range: ' . $startDate . ' to ' . $endDate]);
            fputcsv($file, []);

            fputcsv($file, ['ACCOUNT CODE', 'ACCOUNT', 'STARTING BALANCE', 'DEBIT', 'CREDIT', 'NET MOVEMENT', 'ENDING BALANCE']);

            foreach ($accountBalanceReport->categories as $accountCategoryName => $accountCategory) {
                fputcsv($file, ['', $accountCategoryName]);

                foreach ($accountCategory->accounts as $account) {
                    fputcsv($file, [
                        $account->accountCode,
                        $account->accountName,
                        $account->balance->startingBalance ?? '',
                        $account->balance->debitBalance,
                        $account->balance->creditBalance,
                        $account->balance->netMovement,
                        $account->balance->endingBalance ?? '',
                    ]);
                }

                // Category Summary row
                fputcsv($file, [
                    '',
                    'Total ' . $accountCategoryName,
                    $accountCategory->summary->startingBalance ?? '',
                    $accountCategory->summary->debitBalance,
                    $accountCategory->summary->creditBalance,
                    $accountCategory->summary->netMovement,
                    $accountCategory->summary->endingBalance ?? '',
                ]);

                fputcsv($file, []);
            }

            // Final Row for overall totals
            fputcsv($file, [
                '',
                'Total for all accounts',
                '',
                $accountBalanceReport->overallTotal->debitBalance,
                $accountBalanceReport->overallTotal->creditBalance,
                '',
                '',
            ]);

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
