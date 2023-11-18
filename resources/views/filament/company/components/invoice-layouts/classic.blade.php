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

        <!-- Header Section -->
        <div class="flex justify-between items-start pb-3">
            <!-- Logo -->
            <div style="width: 20%;" class="text-left">
                @if($logo && $show_logo)
                    <img src="{{ URL::asset($logo) }}" alt="logo" style="width: 100%; height: auto">
                @endif
            </div>

            <!-- Company Details -->
            <div style="width: 60%;" class="text-right text-xs text-gray-600 dark:text-gray-200 space-y-1">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $company_name }}</h2>
                @if($company_address && $company_city && $company_state)
                    <p>{{ $company_address }}</p>
                    <p>{{ $company_city }}, {{ $company_state }} {{ $company_zip }}</p>
                    <p>{{ $company_country }}</p>
                @endif
            </div>
        </div>

        <!-- Empty line for spacing -->
        <div class="my-4"></div>

        <!-- Invoice Title/Header Centered -->
        <div class="text-center" style="height: 60px;">
            <h1 class="text-3xl font-semibold text-gray-800 dark:text-white">{{ $header }}</h1>
            @if ($subheader)
                <p class="text-sm text-gray-600">{{ $subheader }}</p>
            @endif
        </div>

        <!-- Billing and Invoice Summary -->
        <div class="flex justify-between pt-4">
            <!-- Label for Billing and Invoice Details -->
            <div class="text-base text-gray-600 dark:text-gray-200 mb-2">
                <h4 class="font-semibold">Bill To:</h4>
            </div>
        </div>

        <div class="flex justify-between" style="height: 60px;">
            <!-- Billing Details -->
            <table style="width: 65%">
                <tbody class="text-xs text-left p-1">
                <tr>
                    <td>John Doe</td>
                </tr>
                <tr>
                    <td>123 Main Street</td>
                </tr>
                <tr>
                    <td>New York, NY 10001</td>
                </tr>
                </tbody>
            </table>

            <!-- Invoice Details -->
            <table style="width: 35%">
                <tbody class="text-xs text-right p-1">
                <tr>
                    <td>Invoice No:</td>
                    <td>{{ $invoice_number }}</td>
                </tr>
                <tr>
                    <td>Invoice Date:</td>
                    <td>{{ $invoice_date }}</td>
                </tr>
                <tr>
                    <td>Invoice Due:</td>
                    <td>{{ $invoice_due_date }}</td>
                </tr>
                </tbody>
            </table>
        </div>


        <!-- Line Items -->
        <div class="my-8">
            <table class="w-full text-sm border-collapse">
                <thead class="border-b-2">
                    <tr>
                        <th class="p-2 w-1/12 text-center">#</th>
                        <th class="p-2 w-5/12 text-left">{{ $item_name }}</th>
                        <th class="p-2 w-2/12 text-right">{{ $unit_name }}</th>
                        <th class="p-2 w-2/12 text-right">{{ $price_name }}</th>
                        <th class="p-2 w-2/12 text-right">{{ $amount_name }}</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    <tr class="border-b">
                        <td class="p-2 text-center">1</td>
                        <td class="p-2 text-left">Web Development</td>
                        <td class="p-2 text-right">10</td>
                        <td class="p-2 text-right">$100.00</td>
                        <td class="p-2 text-right">$1000.00</td>
                    </tr>
                    <tr class="border-b">
                        <td class="p-2 text-center">2</td>
                        <td class="p-2 text-left">Consulting</td>
                        <td class="p-2 text-right">5</td>
                        <td class="p-2 text-right">$200.00</td>
                        <td class="p-2 text-right">$1000.00</td>
                    </tr>
                    <tr class="border-b">
                        <td class="p-2 text-center">3</td>
                        <td class="p-2 text-left">Design Services</td>
                        <td class="p-2 text-right">8</td>
                        <td class="p-2 text-right">$125.00</td>
                        <td class="p-2 text-right">$1000.00</td>
                    </tr>
                </tbody>
            </table>

            <!-- Financial Details and Notes -->
            <div class="flex justify-between text-xs">
                <!-- Notes Section -->
                <div class="w-8/12 border rounded p-2 mt-4">
                    <h4 class="font-semibold mb-2">Notes:</h4>
                    <p>{{ $footer }}</p>
                </div>

                <!-- Financial Summary -->
                <div class="w-4/12 mt-2">
                    <table class="w-full border-collapse">
                        <tbody class="text-right">
                            <tr>
                                <td class="p-2">Subtotal:</td>
                                <td class="p-2">$3000.00</td>
                            </tr>
                            <tr>
                                <td class="p-2">Tax:</td>
                                <td class="p-2">$300.00</td>
                            </tr>
                            <tr>
                                <td class="p-2">Total:</td>
                                <td class="p-2">$3300.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-between mt-8 border-t-2 pt-4">
            <div class="text-xs text-gray-600 dark:text-gray-300">
                <h4 class="font-semibold mb-2">Terms & Conditions:</h4>
                <p>{{ $terms }}</p>
            </div>
        </div>
    </div>
</div>
