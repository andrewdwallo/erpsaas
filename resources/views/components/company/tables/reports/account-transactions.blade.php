@php
    use App\Filament\Company\Pages\Accounting\Transactions;
    use App\Models\Accounting\Bill;
    use App\Filament\Company\Resources\Purchases\BillResource\Pages\ViewBill;
    use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ViewInvoice;

    $iconPosition = \Filament\Support\Enums\IconPosition::After;
@endphp

<table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
    <x-company.tables.header :headers="$report->getHeaders()" :alignmentClass="[$report, 'getAlignmentClass']"/>
    @foreach($report->getCategories() as $categoryIndex => $category)
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
        <!-- Category Header -->
        <tr class="bg-gray-50 dark:bg-white/5">
            <x-filament-tables::cell tag="th" colspan="{{ count($report->getHeaders()) }}" class="text-left">
                <div class="px-3 py-3.5">
                    @foreach ($category->header as $headerRow)
                        <div
                            class="text-sm {{ $loop->first ? 'font-semibold text-gray-950 dark:text-white' : 'font-normal text-gray-500 dark:text-white/50' }}">
                            @foreach ($headerRow as $headerValue)
                                @if (!empty($headerValue))
                                    {{ $headerValue }}
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </x-filament-tables::cell>
        </tr>
        <!-- Transactions Data -->
        @foreach($category->data as $dataIndex => $transaction)
            <tr
                @class([
                    'bg-gray-50 dark:bg-white/5' => $loop->first || $loop->last || $loop->remaining === 1,
                ])
            >
                @foreach($transaction as $cellIndex => $cell)
                    <x-filament-tables::cell
                        @class([
                           $report->getAlignmentClass($cellIndex),
                           'whitespace-normal' => $cellIndex === 1,
                       ])
                    >
                        <div
                            @class([
                                'px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white',
                                'font-semibold' => $loop->parent->first || $loop->parent->last || $loop->parent->remaining === 1,
                            ])
                        >
                            @if(is_array($cell) && isset($cell['description']))
                                @if(isset($cell['id']) && $cell['tableAction'])
                                    @if($cell['tableAction']['type'] === 'transaction')
                                        <x-filament::link
                                            :href="Transactions::getUrl(parameters: [
                                                'tableAction' => $cell['tableAction']['action'],
                                                'tableActionRecord' => $cell['tableAction']['id'],
                                            ])"
                                            target="_blank"
                                            color="primary"
                                            icon="heroicon-o-arrow-top-right-on-square"
                                            :icon-position="$iconPosition"
                                            icon-size="w-4 h-4 min-w-4 min-h-4"
                                        >
                                            {{ $cell['description'] }}
                                        </x-filament::link>
                                    @else
                                        <x-filament::link
                                            :href="$cell['tableAction']['model'] === Bill::class
                                                ? ViewBill::getUrl(['record' => $cell['tableAction']['id']])
                                                : ViewInvoice::getUrl(['record' => $cell['tableAction']['id']])"
                                            target="_blank"
                                            color="primary"
                                            icon="heroicon-o-arrow-top-right-on-square"
                                            :icon-position="$iconPosition"
                                            icon-size="w-4 h-4 min-w-4 min-h-4"
                                        >
                                            {{ $cell['description'] }}
                                        </x-filament::link>
                                    @endif
                                @else
                                    {{ $cell['description'] }}
                                @endif
                            @else
                                {{ $cell }}
                            @endif
                        </div>
                    </x-filament-tables::cell>
                @endforeach
            </tr>
        @endforeach
        <!-- Spacer Row -->
        @unless($loop->last)
            <tr>
                <td colspan="{{ count($report->getHeaders()) }}">
                    <div class="min-h-12"></div>
                </td>
            </tr>
        @endunless
        </tbody>
    @endforeach
</table>
