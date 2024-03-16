<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Balances</title>
    <style>
        @page {
            size: A4;
            margin: 8.5mm 8.5mm 30mm 8.5mm;
        }

        .header {
            color: #374151;
        }

        .table-class th,
        .table-class td {
            text-align: right;
            color: #374151;
        }

        /* Align the first column header and data to the left */
        .table-class th:first-child, .table-class td:first-child {
            text-align: left;
        }

        .header {
            margin-bottom: 1rem; /* Space between header and table */
        }

        .header .title,
        .header .company-name,
        .header .date-range {
            margin-bottom: 0.125rem; /* Uniform space between header elements */
        }

        .title { font-size: 1.5rem; }
        .company-name { font-size: 1.125rem; font-weight: 600; }
        .date-range { font-size: 0.875rem; }

        .table-class {
            width: 100%;
            border-collapse: collapse;
        }

        .table-class th,
        .table-class td {
            padding: 0.75rem;
            font-size: 0.75rem;
            line-height: 1rem;
            border-bottom: 1px solid #d1d5db; /* Gray border for all rows */
        }

        .category-row > td {
            background-color: #f3f4f6; /* Gray background for category names */
            font-weight: 600;
        }

        .table-body tr { background-color: #ffffff; /* White background for other rows */ }

        .spacer-row > td { height: 0.75rem; }

        .summary-row > td,
        .table-footer-row > td {
            font-weight: 600;
            background-color: #ffffff; /* White background for footer */
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Account Balances</div>
        <div class="company-name">{{ auth()->user()->currentCompany->name }}</div>
        <div class="date-range">Date Range: {{ $startDate }} to {{ $endDate }}</div>
    </div>
    <table class="table-class">
        <thead class="table-head" style="display: table-row-group;">
            <tr>
                <th>Account</th>
                <th>Starting Balance</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Net Movement</th>
                <th>Ending Balance</th>
            </tr>
        </thead>
        @foreach($accountBalanceReport->categories as $accountCategoryName => $accountCategory)
            <tbody>
            <tr class="category-row">
                <td colspan="6">{{ $accountCategoryName }}</td>
            </tr>
            @foreach($accountCategory->accounts as $account)
                <tr>
                    <td>{{ $account->accountName }}</td>
                    <td>{{ $account->balance->startingBalance ?? '' }}</td>
                    <td>{{ $account->balance->debitBalance }}</td>
                    <td>{{ $account->balance->creditBalance }}</td>
                    <td>{{ $account->balance->netMovement }}</td>
                    <td>{{ $account->balance->endingBalance ?? '' }}</td>
                </tr>
            @endforeach
            <tr class="summary-row">
                <td>Total {{ $accountCategoryName }}</td>
                <td>{{ $accountCategory->summary->startingBalance ?? '' }}</td>
                <td>{{ $accountCategory->summary->debitBalance }}</td>
                <td>{{ $accountCategory->summary->creditBalance }}</td>
                <td>{{ $accountCategory->summary->netMovement }}</td>
                <td>{{ $accountCategory->summary->endingBalance ?? '' }}</td>
            </tr>
            <tr class="spacer-row">
                <td colspan="6"></td>
            </tr>
            </tbody>
        @endforeach
        <tfoot>
            <tr class="table-footer-row">
                <td>Total for all accounts</td>
                <td></td>
                <td>{{ $accountBalanceReport->overallTotal->debitBalance }}</td>
                <td>{{ $accountBalanceReport->overallTotal->creditBalance }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
