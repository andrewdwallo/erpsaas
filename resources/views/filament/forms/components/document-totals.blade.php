@php
    use App\Enums\Accounting\DocumentDiscountMethod;
    use App\Utilities\Currency\CurrencyAccessor;
    use App\View\Models\DocumentTotalViewModel;

    $data = $this->form->getRawState();
    $type = $getType();
    $viewModel = new DocumentTotalViewModel($data, $type);
    extract($viewModel->buildViewData(), EXTR_SKIP);
@endphp

<div class="totals-summary w-full lg:pl-16 lg:pr-24 py-8 lg:py-0">
    <table class="w-full text-right table-fixed hidden lg:table">
        <colgroup>
            <col class="w-[30%]"> {{-- Items --}}
            <col class="w-[10%]"> {{-- Quantity --}}
            <col class="w-[10%]"> {{-- Price --}}
            <col class="w-[15%]">
            <col class="w-[15%]"> {{-- Adjustments --}}
            <col class="w-[10%]"> {{-- Amount --}}
        </colgroup>
        <tbody>
        @if($subtotal)
            <tr>
                <td colspan="4"></td>
                <td class="text-sm p-2 font-semibold text-gray-950 dark:text-white">Subtotal:</td>
                <td class="text-sm p-2">{{ $subtotal }}</td>
            </tr>
        @endif
        @if($taxTotal)
            <tr>
                <td colspan="4"></td>
                <td class="text-sm p-2">Tax:</td>
                <td class="text-sm p-2">{{ $taxTotal }}</td>
            </tr>
        @endif
        @if($isPerDocumentDiscount)
            <tr>
                <td colspan="3" class="text-sm p-2">Discount:</td>
                <td colspan="2" class="text-sm p-2">
                    <div class="flex justify-between space-x-2">
                        @foreach($getChildComponentContainer()->getComponents() as $component)
                            <div class="flex-1 text-left">{{ $component }}</div>
                        @endforeach
                    </div>
                </td>
                <td class="text-sm p-2">({{ $discountTotal }})</td>
            </tr>
        @elseif($discountTotal)
            <tr>
                <td colspan="4"></td>
                <td class="text-sm p-2">Discount:</td>
                <td class="text-sm p-2">({{ $discountTotal }})</td>
            </tr>
        @endif
        <tr>
            <td colspan="4"></td>
            <td class="text-sm p-2 font-semibold text-gray-950 dark:text-white">{{ $amountDue ? 'Total' : 'Grand Total' }}:</td>
            <td class="text-sm p-2">{{ $grandTotal }}</td>
        </tr>
        @if($amountDue)
            <tr>
                <td colspan="4"></td>
                <td class="text-sm p-2 font-semibold text-gray-950 dark:text-white border-t-4 border-double">Amount Due
                    ({{ $currencyCode }}):
                </td>
                <td class="text-sm p-2 border-t-4 border-double">{{ $amountDue }}</td>
            </tr>
        @endif
        @if($conversionMessage)
            <tr>
                <td colspan="6" class="text-sm p-2 text-gray-600">
                    {{ $conversionMessage }}
                </td>
            </tr>
        @endif
        </tbody>
    </table>

    <!-- Mobile View -->
    <div class="block lg:hidden">
        <div class="flex flex-col space-y-6">
            @if($subtotal)
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-950 dark:text-white">Subtotal:</span>
                    <span class="text-sm">{{ $subtotal }}</span>
                </div>
            @endif
            @if($taxTotal)
                <div class="flex justify-between items-center">
                    <span class="text-sm">Tax:</span>
                    <span class="text-sm">{{ $taxTotal }}</span>
                </div>
            @endif
            @if($isPerDocumentDiscount)
                <div class="flex flex-col space-y-2">
                    <span class="text-sm">Discount:</span>
                    <div class="flex justify-between space-x-2">
                        @foreach($getChildComponentContainer()->getComponents() as $component)
                            <div class="w-1/2">{{ $component }}</div>
                        @endforeach
                    </div>
                </div>
            @elseif($discountTotal)
                <div class="flex justify-between items-center">
                    <span class="text-sm">Discount:</span>
                    <span class="text-sm">({{ $discountTotal }})</span>
                </div>
            @endif
            <div class="flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $amountDue ? 'Total' : 'Grand Total' }}:</span>
                <span class="text-sm">{{ $grandTotal }}</span>
            </div>
            @if($amountDue)
                <div class="flex justify-between items-center">
                    <span
                        class="text-sm font-semibold text-gray-950 dark:text-white">Amount Due ({{ $currencyCode }}):</span>
                    <span class="text-sm">{{ $amountDue }}</span>
                </div>
            @endif
            @if($conversionMessage)
                <div class="text-sm text-gray-600">
                    {{ $conversionMessage }}
                </div>
            @endif
        </div>
    </div>
</div>
