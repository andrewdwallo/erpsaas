@php
    $data = $this->form->getRawState();
    $viewModel = new \App\View\Models\InvoiceViewModel($this->record, $data);
    $viewSpecial = $viewModel->buildViewData();
    extract($viewSpecial,\EXTR_SKIP);
@endphp

{!! $font_html !!}

<style>
    .inv-paper {
        font-family: '{{ $font_family }}', sans-serif;
    }
</style>

<x-company.invoice.container class="classic-template-container">
    <!-- Header Section -->
    <x-company.invoice.header class="default-template-header">
        <div class="w-2/3 text-left ml-6">
            <div class="text-xs">
                <h2 class="text-base font-semibold">{{ $company_name }}</h2>
                @if($company_address && $company_city && $company_state && $company_zip)
                    <p>{{ $company_address }}</p>
                    <p>{{ $company_city }}, {{ $company_state }} {{ $company_zip }}</p>
                    <p>{{ $company_country }}</p>
                @endif
            </div>
        </div>

        <div class="w-1/3 flex justify-end mr-6">
            @if($logo && $show_logo)
                <x-company.invoice.logo :src="$logo"/>
            @endif
        </div>
    </x-company.invoice.header>

    <x-company.invoice.metadata class="classic-template-metadata">
        <div class="items-center flex">
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $accent_color }};">
            <div class="items-center flex mx-5">
                <x-icons.decor-border-left color="{{ $accent_color }}"/>
                <div class="px-2.5 border-solid border-y-2 py-1 -mx-3" style="border-color: {{ $accent_color }};">
                    <div class="px-2.5 border-solid border-y-2 py-3" style="border-color: {{ $accent_color }};">
                        <div class="inline text-2xl font-semibold"
                             style="color: {{ $accent_color }};">{{ $header }}</div>
                    </div>
                </div>
                <x-icons.decor-border-right color="{{ $accent_color }}"/>
            </div>
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $accent_color }};">
        </div>
        <div class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400">{{ $subheader }}</div>

        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-xs">
                <h3 class="text-gray-600 dark:text-gray-400 font-medium tracking-tight mb-1">BILL TO</h3>
                <p class="text-base font-bold">John Doe</p>
                <p>123 Main Street</p>
                <p>New York, New York 10001</p>
                <p>United States</p>
            </div>

            <div class="text-xs">
                <table class="min-w-full">
                    <tbody>
                    <tr>
                        <td class="font-semibold text-right pr-2">Invoice Number:</td>
                        <td class="text-left pl-2">{{ $invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">Invoice Date:</td>
                        <td class="text-left pl-2">{{ $invoice_date }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">Payment Due:</td>
                        <td class="text-left pl-2">{{ $invoice_due_date }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.invoice.metadata>

    <!-- Line Items -->
    <x-company.invoice.line-items class="classic-template-line-items px-6">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-8">
            <tr>
                <th class="text-left">{{ $item_name }}</th>
                <th class="text-center">{{ $unit_name }}</th>
                <th class="text-right">{{ $price_name }}</th>
                <th class="text-right">{{ $amount_name }}</th>
            </tr>
            </thead>
            <tbody class="text-xs border-t-2 border-b-2 border-dotted border-gray-300 leading-8">
            <tr>
                <td class="text-left font-semibold">Item 1</td>
                <td class="text-center">2</td>
                <td class="text-right">$150.00</td>
                <td class="text-right">$300.00</td>
            </tr>
            <tr>
                <td class="text-left font-semibold">Item 2</td>
                <td class="text-center">3</td>
                <td class="text-right">$200.00</td>
                <td class="text-right">$600.00</td>
            </tr>
            <tr>
                <td class="text-left font-semibold">Item 3</td>
                <td class="text-center">1</td>
                <td class="text-right">$180.00</td>
                <td class="text-right">$180.00</td>
            </tr>
            </tbody>
        </table>

        <!-- Financial Details and Notes -->
        <div class="flex justify-between text-xs space-x-1">
            <!-- Notes Section -->
            <div class="w-1/2 border border-dashed border-gray-300 p-2 mt-4">
                <h4 class="font-semibold mb-2">Notes</h4>
                <p>{{ $footer }}</p>
            </div>

            <!-- Financial Summary -->
            <div class="w-1/2 mt-2">
                <table class="w-full table-fixed">
                    <tbody class="text-xs leading-loose">
                    <tr>
                        <td class="text-right font-semibold">Subtotal:</td>
                        <td class="text-right">$1080.00</td>
                    </tr>
                    <tr class="text-success-800 dark:text-success-600">
                        <td class="text-right">Discount (5%):</td>
                        <td class="text-right">($54.00)</td>
                    </tr>
                    <tr>
                        <td class="text-right">Sales Tax (10%):</td>
                        <td class="text-right">$102.60</td>
                    </tr>
                    <tr>
                        <td class="text-right font-semibold">Total:</td>
                        <td class="text-right">$1128.60</td>
                    </tr>
                    <tr>
                        <td class="text-right font-semibold">Amount Due (USD):</td>
                        <td class="text-right">$1128.60</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.invoice.line-items>

    <!-- Footer -->
    <x-company.invoice.footer class="classic-template-footer">
        <h4 class="font-semibold px-6 mb-2">Terms & Conditions</h4>
        <p class="px-6 break-words line-clamp-4">{{ $terms }}</p>
    </x-company.invoice.footer>
</x-company.invoice.container>
