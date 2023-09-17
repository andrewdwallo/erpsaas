@php
    $data = $this->form->getRawState();
    $viewModel = new \App\View\Models\InvoiceViewModel($this->record, $data);
    $viewSpecial = $viewModel->buildViewData();
    extract($viewSpecial);
@endphp

{!! $font_html !!}

<style>
    .paper {
        font-family: '{{ $font_family }}', sans-serif;
    }
</style>

<div class="print-template flex justify-center p-6">
    <div class="paper bg-[#ffffff] dark:bg-gray-950 p-8 rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.1)] w-[612px] h-[791px]">

        <!-- Header: Logo on the left and Company details on the right -->
        <div class="flex mb-4">
            <div class="w-2/5">
                @if($logo && $show_logo)
                    <div class="text-left">
                        <img src="{{ \Illuminate\Support\Facades\URL::asset($logo) }}" alt="logo" style="width: 120px; height: auto">
                    </div>
                @endif
            </div>

            <!-- Company Details -->
            <div class="w-3/5">
                <div class="text-xs text-gray-600 dark:text-gray-200 text-right space-y-1">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $company_name }}</h2>
                    @if($company_address && $company_city && $company_state && $company_zip)
                        <p>{{ $company_address }}</p>
                        <p>{{ $company_city }}, {{ $company_state }} {{ $company_zip }}</p>
                        <p>{{ $company_country }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Border Line -->
        <div class="border-b-2 my-4" style="border-color: {{ $accent_color }}"></div>

        <!-- Invoice Details -->
        <div class="flex mb-4">
            <div class="w-2/5">
                <div class="text-left">
                    <h1 class="text-3xl font-semibold text-gray-800 dark:text-white">{{ $header }}</h1>
                    @if ($subheader)
                        <p class="text-sm text-gray-600 dark:text-gray-100">{{ $subheader }}</p>
                    @endif
                </div>
            </div>

            <div class="w-3/5">
                <div class="text-right">
                    <p>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-100">No: </span>
                        <span class="text-xs text-gray-700 dark:text-white">{{ $invoice_number }}</span>
                    </p>
                    <p>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-100">Date: </span>
                        <span class="text-xs text-gray-500 dark:text-white">{{ $invoice_date }}</span>
                    </p>
                    <p>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-100">Due Date: </span>
                        <span class="text-xs text-gray-500 dark:text-white">{{ $invoice_due_date }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Billing Details -->
        <div class="text-xs text-gray-600 dark:text-gray-200 mb-4">
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-200 mb-2">BILL TO</h3>
            <p class="text-sm text-gray-800 dark:text-white font-semibold">John Doe</p>
            <p>123 Main Street</p>
            <p>New York, NY 10001</p>
        </div>

        <!-- Line Items Table -->
        <div class="mb-4">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr style="color: {{ $accent_color }}">
                        <th class="text-left p-2 w-1/2">{{ $item_name }}</th>
                        <th class="text-center p-2 w-1/6">{{ $unit_name }}</th>
                        <th class="text-center p-2 w-1/6">{{ $price_name }}</th>
                        <th class="text-center p-2 w-1/6">{{ $amount_name }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-gray-200/75 dark:bg-gray-800">
                        <td class="p-2">Item 1</td>
                        <td class="p-2 text-center">2</td>
                        <td class="p-2 text-center">$150.00</td>
                        <td class="p-2 text-center">$300.00</td>
                    </tr>
                    <tr>
                        <td class="p-2">Item 2</td>
                        <td class="p-2 text-center">3</td>
                        <td class="p-2 text-center">$200.00</td>
                        <td class="p-2 text-center">$600.00</td>
                    </tr>
                    <tr class="bg-gray-200/75 dark:bg-gray-800">
                        <td class="p-2">Item 3</td>
                        <td class="p-2 text-center">1</td>
                        <td class="p-2 text-center">$200.00</td>
                        <td class="p-2 text-center">$200.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total Amount -->
        <div class="text-right mb-8">
            <p class="text-sm text-gray-600 dark:text-gray-200">Subtotal: $1100.00</p>
            <p class="text-sm text-gray-600 dark:text-gray-200">Tax: $110.00</p>
            <p class="text-lg font-semibold text-gray-800 dark:text-white">Total: $1210.00</p>
        </div>

        <!-- Footer Notes -->
        <div class="pt-6 text-gray-600 dark:text-gray-300 text-xs">
            <p>{{ $footer }}</p>
            <div class="mt-2 border-t-2 border-gray-300 py-2">
                <h4 class="font-semibold text-gray-700 dark:text-gray-100 mb-2">Terms & Conditions:</h4>
                <p>{{ $terms }}</p>
            </div>
        </div>
    </div>
</div>
