@php
    $data = $this->form->getState();
    $viewModel = new \App\View\Models\InvoiceViewModel($this->invoice, $data);
    $viewSpecial = $viewModel->buildViewData();
    extract($viewSpecial);
@endphp

<div class="print-template flex justify-center p-6">
    <div class="paper bg-white dark:bg-gray-900 p-8 rounded-lg shadow-[0_0_10px_rgba(0,0,0,0.1)] w-[612px] h-[791px]">

        <!-- Colored Header with Logo -->
        <div class="flex">
            <div class="text-white py-3 flex items-start justify-start bg-gray-800" style="height: 80px; width: 85%;">
                @if($document_logo)
                    <div class="text-left">
                        <img src="{{ \Illuminate\Support\Facades\URL::asset($document_logo) }}" alt="logo" style="width: 120px; height: auto">
                    </div>
                @endif
            </div>

            <!-- Ribbon Container -->
            <div class="text-white flex flex-col justify-end p-2" style="background: {{ $accent_color }}; width: 30%; height: 120px; margin-left: -15%;">
                @if($title)
                    <div class="text-center align-bottom">
                        <h1 class="text-3xl font-bold">{{ $title }}</h1>
                    </div>
                @endif
            </div>
        </div>

        <!-- Company Details -->
        <div class="flex justify-between">
            <div class="text-xs text-gray-600 dark:text-gray-200 space-y-1">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">{{ $company_name }}</h2>
                @if($company_address && $company_city && $company_state && $company_zip)
                    <p>{{ $company_address }}</p>
                    <p>{{ $company_city }}, {{ $company_state }} {{ $company_zip }}</p>
                @endif
            </div>

            <table class="mt-6" style="width: 35%; height: 100px">
                <thead class="border-b-2" style="border-color: {{ $accent_color }}">
                <tr class="p-1">
                    <th class="text-xs text text-gray-500 text-right">Total Due</th>
                    <th class="text-xs text-gray-500 text-left">:</th>
                    <th class="text-sm font-semibold text-gray-800 dark:text-white text-right">USD $1100.00</th>
                </tr>
                </thead>
                <tr>
                    <td colspan="3" class="py-1"></td>
                </tr>
                <tbody class="text-xs text-gray-500 dark:text-white">
                <tr class="p-1">
                    <td class="text-right">Invoice No</td>
                    <td class="text-left">:</td>
                    <td class="text-right">{{ $invoice_number }}</td>
                </tr>
                <tr class="p-1">
                    <td class="text-right">Invoice Date</td>
                    <td class="text-left">:</td>
                    <td class="text-right">{{ $invoice_date }}</td>
                </tr>
                <tr class="p-1">
                    <td class="text-right">Invoice Due</td>
                    <td class="text-left">:</td>
                    <td class="text-right">{{ $invoice_due_date }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Billing Details -->
        <div class="text-xs text-gray-600 dark:text-gray-200 mb-4">
            <h3 class="text-base font-semibold text-gray-600 dark:text-gray-200 tracking-tight mb-2">BILL TO</h3>
            <p class="text-lg font-semibold" style="color: {{ $accent_color }}">John Doe</p>
            <p>123 Main Street</p>
            <p>New York, NY 10001</p>
        </div>

        <!-- Line Items Table -->
        <div class="mb-8">
            <table class="w-full border-collapse text-sm">
                <thead style="background: {{ $accent_color }};">
                <tr class="text-white">
                    <th class="text-left p-2 w-1/12">No</th>
                    <th class="text-left p-2 w-7/12">{{ $item_column }}</th>
                    <th class="text-left p-2 w-1/6">{{ $unit_column }}</th>
                    <th class="text-left p-2 w-1/6">{{ $price_column }}</th>
                    <th class="text-left p-2 w-1/6">{{ $amount_column }}</th>
                </tr>
                </thead>
                <tbody class="text-xs">
                <tr class="border-b border-gray-300">
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">01</td>
                    <td class="p-2">Item 1</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">2</td>
                    <td class="p-2">$150.00</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$300.00</td>
                </tr>
                <tr class="border-b border-gray-300">
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">02</td>
                    <td class="p-2">Item 2</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">3</td>
                    <td class="p-2">$200.00</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$600.00</td>
                </tr>
                <tr class="border-b border-gray-300">
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">03</td>
                    <td class="p-2">Item 3</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">1</td>
                    <td class="p-2">$180.00</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$180.00</td>
                </tr>
                <tr>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2">Subtotal:</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$1000.00</td>
                </tr>
                <tr>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2">Tax:</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$100.00</td>
                </tr>
                <tr>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2"></td>
                    <td class="p-2">Total:</td>
                    <td class="p-2 bg-gray-100 dark:bg-gray-800">$1100.00</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer Notes -->
        <div class="text-gray-600 dark:text-gray-300 text-xs">
            <h4 class="font-semibold text-gray-700 dark:text-gray-100 mb-2" style="color: {{ $accent_color }}">Terms & Conditions:</h4>
            <div class="flex mt-2 justify-between py-2 border-t-2 border-gray-300">
                <div class="w-1/2">
                    <p>{{ $terms }}</p>
                </div>
                <div class="w-1/2 text-right">
                    <p>{{ $footer }}</p>
                </div>
            </div>
        </div>
    </div>
</div>












