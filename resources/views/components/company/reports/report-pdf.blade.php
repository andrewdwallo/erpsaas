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
        <thead class="table-head">
        <tr>
            @foreach($report->getHeaders() as $index => $header)
                <th class="{{ $report->getAlignmentClass($index) }}">
                    {{ $header }}
                </th>
            @endforeach
        </tr>
        </thead>
        @foreach($report->getCategories() as $category)
            <tbody>
            @if(! empty($category->header))
                <tr class="category-header-row">
                    @foreach($category->header as $index => $header)
                        <td class="{{ $report->getAlignmentClass($index) }}">
                            {{ $header }}
                        </td>
                    @endforeach
                </tr>
            @endif
            @foreach($category->data as $account)
                <tr>
                    @foreach($account as $index => $cell)
                        <td @class([
                            $report->getAlignmentClass($index),
                            'whitespace-normal' => $index === 'account_name',
                            'whitespace-nowrap' => $index !== 'account_name',
                        ])
                        >
                            @if(is_array($cell) && isset($cell['name']))
                                {{ $cell['name'] }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach

            <!-- Category Types -->
            @foreach($category->types ?? [] as $type)
                <!-- Type Header -->
                <tr class="type-header-row">
                    @foreach($type->header as $index => $header)
                        <td @class([
                            $report->getAlignmentClass($index),
                            'type-row-indent' => $index === 'account_name',
                        ])
                        >
                            {{ $header }}
                        </td>
                    @endforeach
                </tr>

                <!-- Type Data -->
                @foreach($type->data as $typeRow)
                    <tr class="type-data-row">
                        @foreach($typeRow as $index => $cell)
                            <td @class([
                                $report->getAlignmentClass($index),
                                'whitespace-normal type-row-indent' => $index === 'account_name',
                                'whitespace-nowrap' => $index !== 'account_name',
                            ])
                            >
                                @if(is_array($cell) && isset($cell['name']))
                                    {{ $cell['name'] }}
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <!-- Type Summary -->
                <tr class="type-summary-row">
                    @foreach($type->summary as $index => $cell)
                        <td @class([
                            $report->getAlignmentClass($index),
                            'type-row-indent' => $index === 'account_name',
                        ])
                        >
                            {{ $cell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach

            @if(! empty($category->summary))
                <tr class="category-summary-row">
                    @foreach($category->summary as $index => $cell)
                        <td class="{{ $report->getAlignmentClass($index) }}">
                            {{ $cell }}
                        </td>
                    @endforeach
                </tr>

                @unless($loop->last && empty($report->getOverallTotals()))
                    <tr class="spacer-row">
                        <td colspan="{{ count($report->getHeaders()) }}"></td>
                    </tr>
                @endunless
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
@endsection
