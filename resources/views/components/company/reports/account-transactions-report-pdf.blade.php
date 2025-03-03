@extends('components.company.reports.layout')

@section('content')
    <div class="header">
        <div class="title">{{ $report->getTitle() }}</div>
        <div class="company-name">{{ $company->name }}</div>
        <div class="date-range">Date Range: {{ $startDate }} to {{ $endDate }}</div>
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
            <tr class="category-header-row">
                <td colspan="{{ count($report->getHeaders()) }}">
                    <div>
                        @foreach($category->header as $headerRow)
                            <div>
                                @foreach($headerRow as $headerValue)
                                    @if (!empty($headerValue))
                                        {{ $headerValue }}
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
            @foreach($category->data as $dataIndex => $transaction)
                <tr
                    @class([
                        'category-header-row' => $loop->first || $loop->last || $loop->remaining === 1,
                    ])>
                    @foreach($transaction as $cellIndex => $cell)
                        <td @class([
                            $report->getAlignmentClass($cellIndex),
                            'whitespace-normal' => $cellIndex === 'description',
                            'whitespace-nowrap' => $cellIndex !== 'description',
                        ])
                        >
                            @if(is_array($cell) && isset($cell['description']))
                                {{ $cell['description'] }}
                            @else
                                {{ $cell }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            @unless($loop->last)
                <tr class="spacer-row">
                    <td colspan="{{ count($report->getHeaders()) }}"></td>
                </tr>
            @endunless
            </tbody>
        @endforeach
    </table>
@endsection
