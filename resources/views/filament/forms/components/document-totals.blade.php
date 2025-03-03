@php
    use App\Enums\Accounting\DocumentDiscountMethod;
    use App\Utilities\Currency\CurrencyAccessor;
    use App\View\Models\DocumentTotalViewModel;

    $data = $this->form->getRawState();
    $type = $getType();
    $viewModel = new DocumentTotalViewModel($data, $type);
    extract($viewModel->buildViewData(), EXTR_SKIP);

    $discountMethod = DocumentDiscountMethod::parse($data['discount_method']);
    $isPerDocumentDiscount = $discountMethod->isPerDocument();
@endphp

<div class="totals-summary w-full sm:pr-14">
    <table class="w-full text-right table-fixed hidden sm:table">
        <colgroup>
            <col class="w-[20%]"> {{-- Items --}}
            <col class="w-[30%]"> {{-- Description --}}
            <col class="w-[10%]"> {{-- Quantity --}}
            <col class="w-[10%]"> {{-- Price --}}
            <col class="w-[20%]"> {{-- Taxes --}}
            <col class="w-[10%]"> {{-- Amount --}}
        </colgroup>
        <tbody>
            <tr>
                <td colspan="4"></td>
                <td class="text-sm px-4 py-2 font-medium leading-6 text-gray-950 dark:text-white">Subtotal:</td>
                <td class="text-sm pl-4 py-2 leading-6">{{ $subtotal }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td class="text-sm px-4 py-2 font-medium leading-6 text-gray-950 dark:text-white">Taxes:</td>
                <td class="text-sm pl-4 py-2 leading-6">{{ $taxTotal }}</td>
            </tr>
            @if($isPerDocumentDiscount)
                <tr>
                    <td colspan="3" class="text-sm px-4 py-2 font-medium leading-6 text-gray-950 dark:text-white text-right">Discount:</td>
                    <td colspan="2" class="text-sm px-4 py-2">
                        <div class="flex justify-between space-x-2">
                            @foreach($getChildComponentContainer()->getComponents() as $component)
                                <div class="flex-1 text-left">{{ $component }}</div>
                            @endforeach
                        </div>
                    </td>
                    <td class="text-sm pl-4 py-2 leading-6">({{ $discountTotal }})</td>
                </tr>
            @else
                <tr>
                    <td colspan="4"></td>
                    <td class="text-sm px-4 py-2 font-medium leading-6 text-gray-950 dark:text-white">Discounts:</td>
                    <td class="text-sm pl-4 py-2 leading-6">({{ $discountTotal }})</td>
                </tr>
            @endif
            <tr class="font-semibold">
                <td colspan="4"></td>
                <td class="text-sm px-4 py-2 font-medium leading-6 text-gray-950 dark:text-white">Total:</td>
                <td class="text-sm pl-4 py-2 leading-6">{{ $grandTotal }}</td>
            </tr>
            @if($conversionMessage)
                <tr>
                    <td colspan="6" class="text-sm pl-4 py-2 leading-6 text-gray-600">
                        {{ $conversionMessage }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Mobile View -->
    <div class="block sm:hidden p-5">
        <div class="flex flex-col space-y-6">
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Subtotal:</span>
                <span class="text-sm text-gray-950 dark:text-white">{{ $subtotal }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Taxes:</span>
                <span class="text-sm text-gray-950 dark:text-white">{{ $taxTotal }}</span>
            </div>
            @if($isPerDocumentDiscount)
                <div class="flex flex-col space-y-2">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Discount:</span>
                    <div class="flex justify-between space-x-2">
                        @foreach($getChildComponentContainer()->getComponents() as $component)
                            <div class="w-1/2">{{ $component }}</div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Discounts:</span>
                    <span class="text-sm text-gray-950 dark:text-white">({{ $discountTotal }})</span>
                </div>
            @endif
            <div class="flex justify-between items-center font-semibold">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Total:</span>
                <span class="text-sm text-gray-950 dark:text-white">{{ $grandTotal }}</span>
            </div>
            @if($conversionMessage)
                <div class="text-sm text-gray-600">
                    {{ $conversionMessage }}
                </div>
            @endif
        </div>
    </div>
</div>
