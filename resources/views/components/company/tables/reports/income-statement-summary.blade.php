<table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/5">
    <colgroup>
        <col span="1" style="width: 65%;">
        <col span="1" style="width: 35%;">
    </colgroup>
    <x-company.tables.header :headers="$report->getSummaryHeaders()"
                             :alignment-class="[$report, 'getAlignmentClass']"/>
    @foreach($report->getSummaryCategories() as $accountCategory)
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
        <tr>
            @foreach($accountCategory->summary as $accountCategorySummaryIndex => $accountCategorySummaryCell)
                <x-company.tables.cell :alignment-class="$report->getAlignmentClass($accountCategorySummaryIndex)">
                    {{ $accountCategorySummaryCell }}
                </x-company.tables.cell>
            @endforeach
        </tr>

        @if($accountCategory->summary['account_name'] === 'Cost of Goods Sold')
            <tr class="bg-gray-50 dark:bg-white/5">
                @foreach($report->getGrossProfit() as $grossProfitIndex => $grossProfitCell)
                    <x-company.tables.cell :alignment-class="$report->getAlignmentClass($grossProfitIndex)" bold="true">
                        {{ $grossProfitCell }}
                    </x-company.tables.cell>
                @endforeach
            </tr>
        @endif
        </tbody>
    @endforeach
    <x-company.tables.footer :totals="$report->getSummaryOverallTotals()"
                             :alignment-class="[$report, 'getAlignmentClass']"/>
</table>
