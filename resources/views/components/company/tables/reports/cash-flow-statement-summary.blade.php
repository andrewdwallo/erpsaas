<table class="w-full table-fixed divide-y divide-gray-200 dark:divide-white/5">
    <colgroup>
        <col span="1" style="width: 65%;">
        <col span="1" style="width: 35%;">
    </colgroup>
    <x-company.tables.header :headers="$report->getSummaryHeaders()"
                             :alignment-class="[$report, 'getAlignmentClass']"/>
    @foreach($report->getSummaryCategories() as $accountCategory)
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
        <x-company.tables.category-header :category-headers="$accountCategory->header"
                                          :alignment-class="[$report, 'getAlignmentClass']"/>
        @foreach($accountCategory->types as $accountType)
            <tr>
                @foreach($accountType->summary as $accountTypeSummaryIndex => $accountTypeSummaryCell)
                    <x-company.tables.cell :alignment-class="$report->getAlignmentClass($accountTypeSummaryIndex)">
                        {{ $accountTypeSummaryCell }}
                    </x-company.tables.cell>
                @endforeach
            </tr>
        @endforeach
        <tr>
            @foreach($accountCategory->summary as $accountCategorySummaryIndex => $accountCategorySummaryCell)
                <x-company.tables.cell :alignment-class="$report->getAlignmentClass($accountCategorySummaryIndex)"
                                       bold="true" :underline-bold="$loop->last">
                    {{ $accountCategorySummaryCell }}
                </x-company.tables.cell>
            @endforeach
        </tr>
        <tr>
            <td colspan="{{ count($report->getSummaryHeaders()) }}">
                <div class="min-h-12"></div>
            </td>
        </tr>
        </tbody>
    @endforeach
</table>

<table class="w-full table-fixed divide-y border-t divide-gray-200 dark:divide-white/5">
    <colgroup>
        <col span="1" style="width: 65%;">
        <col span="1" style="width: 35%;">
    </colgroup>
    <x-company.tables.header :headers="$report->getSummaryOverviewHeaders()"
                             :alignment-class="[$report, 'getAlignmentClass']"/>
    @foreach($report->getSummaryOverview() as $overviewCategory)
        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
        <tr class="bg-gray-50 dark:bg-white/5">
            @foreach($overviewCategory->summary as $overviewSummaryIndex => $overviewSummaryCell)
                <x-company.tables.cell :alignment-class="$report->getAlignmentClass($overviewSummaryIndex)" bold="true">
                    {{ $overviewSummaryCell }}
                </x-company.tables.cell>
            @endforeach
        </tr>
        @if($overviewCategory->summary['account_name'] === 'Starting Balance')
            @foreach($report->getSummaryOverviewAlignedWithColumns() as $summaryRow)
                <tr>
                    @foreach($summaryRow as $summaryIndex => $summaryCell)
                        <x-company.tables.cell :alignment-class="$report->getAlignmentClass($summaryIndex)"
                                               :bold="$loop->parent->last"
                                               :underline-bold="$loop->parent->last && $summaryIndex === 'net_movement'"
                                               :underline-thin="$loop->parent->remaining === 1 && $summaryIndex === 'net_movement'"
                        >
                            {{ $summaryCell }}
                        </x-company.tables.cell>
                    @endforeach
                </tr>
            @endforeach
        @endif
        </tbody>
    @endforeach
</table>
