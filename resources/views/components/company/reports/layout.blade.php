<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->getTitle() }}</title>
    <style>
        .font-bold {
            font-weight: bold;
        }

        .category-header-row > td,
        .type-header-row > td {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .category-summary-row > td,
        .table-footer-row > td,
        .type-summary-row > td {
            background-color: #ffffff;
            font-weight: bold;
        }

        .company-name {
            font-size: 1.125rem;
            font-weight: bold;
        }

        .date-range {
            font-size: 0.875rem;
        }

        .header {
            color: #374151;
            margin-bottom: 1rem;
        }

        .header div + div {
            margin-top: 0.5rem;
        }

        .spacer-row > td {
            height: 0.75rem;
        }

        .table-body tr {
            background-color: #ffffff;
        }

        .table-class {
            border-collapse: collapse;
            table-layout: auto;
            width: 100%;
        }

        .table-class .type-row-indent {
            padding-left: 1.5rem;
        }

        .table-class td,
        .table-class th {
            border-bottom: 1px solid #d1d5db;
            color: #374151;
            font-size: 0.75rem;
            line-height: 1rem;
            padding: 0.75rem;
        }

        .table-head {
            display: table-row-group;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .title {
            font-size: 1.5rem;
        }

        .table-class .underline-bold {
            border-bottom: 2px solid #374151;
        }

        .table-class .underline-thin {
            border-bottom: 1px solid #374151;
        }

        .whitespace-normal {
            white-space: normal;
        }

        .whitespace-nowrap {
            white-space: nowrap;
        }

        table tfoot {
            display: table-row-group;
        }
    </style>
</head>
<body>
@yield('content')
</body>
</html>
