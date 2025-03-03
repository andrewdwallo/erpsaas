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
            <tr>
                @foreach($category->summary as $index => $cell)
                    <td @class([
                        $report->getAlignmentClass($index),
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
        <tr class="category-header-row">
            @foreach ($report->getOverallTotals() as $index => $total)
                <td class="{{ $report->getAlignmentClass($index) }}">
                    {{ $total }}
                </td>
            @endforeach
        </tr>
        </tfoot>
    </table>
@endsection
