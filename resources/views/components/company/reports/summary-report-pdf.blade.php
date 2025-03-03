@extends('components.company.reports.layout')

@section('content')
    <div class="header">
        <div class="title">{{ $report->getTitle() }}</div>
        <div class="company-name">{{ $company->name }}</div>
        @if($startDate && $endDate)
            <div class="date-range">Date Range: {{ $startDate }} to {{ $endDate }}</div>
        @else
            <div class="date-range">As of {{ $endDate }}</div>
        @endif
    </div>
    <table class="table-class">
        <colgroup>
            <col span="1" style="width: 65%;">
            <col span="1" style="width: 35%;">
        </colgroup>
        <thead class="table-head">
        <tr>
            @foreach($report->getSummaryHeaders() as $index => $header)
                <th class="{{ $report->getAlignmentClass($index) }}">
                    {{ $header }}
                </th>
            @endforeach
        </tr>
        </thead>
        @foreach($report->getSummaryCategories() as $category)
            <tbody>
            <tr class="category-header-row">
                @foreach($category->header as $index => $header)
                    <td class="{{ $report->getAlignmentClass($index) }}">
                        {{ $header }}
                    </td>
                @endforeach
            </tr>

            <!-- Category Types -->
            @foreach($category->types ?? [] as $type)
                <!-- Type Summary -->
                <tr>
                    @foreach($type->summary as $index => $cell)
                        <td @class([
                            $report->getAlignmentClass($index),
                        ])
                        >
                            {{ $cell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach

            <tr class="category-summary-row">
                @foreach($category->summary as $index => $cell)
                    <td @class([
                        $report->getAlignmentClass($index),
                        'underline-bold' => $loop->last && $report->getTitle() === 'Cash Flow Statement',
                    ])
                    >
                        {{ $cell }}
                    </td>
                @endforeach
            </tr>

            @if($category->summary['account_name'] === 'Cost of Goods Sold')
                <tr class="category-header-row">
                    @foreach($report->getGrossProfit() as $grossProfitIndex => $grossProfitCell)
                        <td class="{{ $report->getAlignmentClass($grossProfitIndex) }}">
                            {{ $grossProfitCell }}
                        </td>
                    @endforeach
                </tr>
            @endif
            </tbody>
        @endforeach
        <tfoot>
        <tr class="table-footer-row">
            @foreach ($report->getOverallTotals() as $index => $total)
                <td class="{{ $report->getAlignmentClass($index) }}">
                    {{ $total }}
                </td>
            @endforeach
        </tr>
        </tfoot>
    </table>

    <!-- Second Overview Table -->
    @if(method_exists($report, 'getSummaryOverviewHeaders') && filled($report->getSummaryOverviewHeaders()))
        <table class="table-class" style="margin-top: 40px;">
            <colgroup>
                <col span="1" style="width: 65%;">
                <col span="1" style="width: 35%;">
            </colgroup>
            <thead class="table-head">
            <tr>
                @foreach($report->getOverviewHeaders() as $index => $header)
                    <th class="{{ $report->getAlignmentClass($index) }}">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
            </thead>
            <!-- Overview Content -->
            @foreach($report->getSummaryOverview() as $overviewCategory)
                <tbody>
                <!-- Summary Row -->
                <tr class="category-header-row">
                    @foreach($overviewCategory->summary as $index => $summaryCell)
                        <td class="{{ $report->getAlignmentClass($index) }}">
                            {{ $summaryCell }}
                        </td>
                    @endforeach
                </tr>

                @if($overviewCategory->summary['account_name'] === 'Starting Balance')
                    @foreach($report->getSummaryOverviewAlignedWithColumns() as $summaryRow)
                        <tr>
                            @foreach($summaryRow as $index => $summaryCell)
                                <td @class([
                                'cell',
                                $report->getAlignmentClass($index),
                                'font-bold' => $loop->parent->last,
                                'underline-thin' => $loop->parent->remaining === 1 && $index === 'net_movement',
                                'underline-bold' => $loop->parent->last && $index === 'net_movement',
                            ])
                                >
                                    {{ $summaryCell }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
                </tbody>
            @endforeach
        </table>
    @endif
@endsection
