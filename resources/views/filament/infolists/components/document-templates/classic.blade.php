<x-company.invoice.container class="classic-template-container">
    <!-- Header Section -->
    <x-company.invoice.header class="default-template-header">
        <div class="w-2/3 text-left ml-6">
            <div class="text-sm tracking-tight">
                <h2 class="text-lg font-semibold">{{ $document->company->name }}</h2>
                @if($formattedAddress = $document->company->getFormattedAddressHtml())
                    {!! $formattedAddress !!}
                @endif
            </div>
        </div>

        <div class="w-1/3 flex justify-end mr-6">
            @if($document->logo && $document->showLogo)
                <x-company.invoice.logo :src="$document->logo"/>
            @endif
        </div>
    </x-company.invoice.header>

    <x-company.invoice.metadata class="classic-template-metadata space-y-8">
        <div class="items-center flex">
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
            <x-icons.document-header-decoration
                color="{{ $document->accentColor }}"
                text="{{ $document->header }}"
                class="w-60"
            />
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
        </div>
        <div class="mt-2 text-base text-center text-gray-600 dark:text-gray-400">{{ $document->subheader }}</div>

        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-sm tracking-tight">
                <h3 class="text-gray-600 dark:text-gray-400 font-medium tracking-tight mb-1">BILL TO</h3>
                <p class="text-base font-bold">{{ $document->client->name }}</p>
                @if($formattedAddress = $document->client->getFormattedAddressHtml())
                    {!! $formattedAddress !!}
                @endif
            </div>

            <div class="text-sm tracking-tight">
                <table class="min-w-full">
                    <tbody>
                    <tr>
                        <td class="font-semibold text-right pr-2">{{ $document->label->number }}:</td>
                        <td class="text-left pl-2">{{ $document->number }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">{{ $document->label->referenceNumber }}:</td>
                        <td class="text-left pl-2">{{ $document->referenceNumber }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">{{ $document->label->date }}:</td>
                        <td class="text-left pl-2">{{ $document->date }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">{{ $document->label->dueDate }}:</td>
                        <td class="text-left pl-2">{{ $document->dueDate }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.invoice.metadata>

    <!-- Line Items -->
    <x-company.invoice.line-items class="classic-template-line-items">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-relaxed">
            <tr>
                <th class="text-left pl-6 w-[50%] py-4">{{ $document->columnLabel->items }}</th>
                <th class="text-center w-[10%] py-4">{{ $document->columnLabel->units }}</th>
                <th class="text-right w-[20%] py-4">{{ $document->columnLabel->price }}</th>
                <th class="text-right pr-6 w-[20%] py-4">{{ $document->columnLabel->amount }}</th>
            </tr>
            </thead>
            <tbody class="text-sm tracking-tight border-y-2 border-dotted border-gray-300">
            @foreach($document->lineItems as $item)
                <tr>
                    <td class="text-left pl-6 font-semibold py-3">
                        {{ $item->name }}
                        @if($item->description)
                            <div class="text-gray-600 font-normal line-clamp-2 mt-1">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center py-3">{{ $item->quantity }}</td>
                    <td class="text-right py-3">{{ $item->unitPrice }}</td>
                    <td class="text-right pr-6 py-3">{{ $item->subtotal }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Financial Details and Notes -->
        <div class="flex justify-between text-sm tracking-tight space-x-1">
            <!-- Notes Section -->
            <div class="w-[60%] border border-dashed border-gray-300 p-2 mt-4">
                <h4 class="font-semibold mb-2">Notes</h4>
                <p>{{ $document->footer }}</p>
            </div>

            <!-- Financial Summary -->
            <div class="w-[40%] mt-2">
                <table class="w-full table-fixed">
                    <tbody class="text-sm tracking-tight">
                    <tr>
                        <td class="text-right font-semibold py-2">Subtotal:</td>
                        <td class="text-right pr-6 py-2">{{ $document->subtotal }}</td>
                    </tr>
                    @if($document->discount)
                        <tr class="text-success-800 dark:text-success-600">
                            <td class="text-right py-2">Discount:</td>
                            <td class="text-right pr-6 py-2">
                                ({{ $document->discount }})
                            </td>
                        </tr>
                    @endif
                    @if($document->tax)
                        <tr>
                            <td class="text-right py-2">Tax:</td>
                            <td class="text-right pr-6 py-2">{{ $document->tax }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-right font-semibold border-t py-2">Total:</td>
                        <td class="text-right border-t pr-6 py-2">{{ $document->total }}</td>
                    </tr>
                    @if($document->amountDue)
                        <tr>
                            <td class="text-right font-semibold border-t-4 border-double py-2">{{ $document->label->amountDue }}
                                ({{ $document->currencyCode }}):
                            </td>
                            <td class="text-right border-t-4 border-double pr-6 py-2">{{ $document->amountDue }}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.invoice.line-items>

    <!-- Footer -->
    <x-company.invoice.footer class="classic-template-footer tracking-tight min-h-48">
        <h4 class="font-semibold px-6 mb-2 text-sm">Terms & Conditions</h4>
        <p class="px-6 break-words line-clamp-4 text-sm">{{ $document->terms }}</p>
    </x-company.invoice.footer>
</x-company.invoice.container>
