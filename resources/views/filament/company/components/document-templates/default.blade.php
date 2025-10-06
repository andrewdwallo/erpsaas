<x-company.document-template.container class="default-template-container" :preview="$preview">

    <x-company.document-template.header class="default-template-header border-b">
        <div class="w-1/3">
            @if($document->logo && $document->showLogo)
                <x-company.document-template.logo :src="$document->logo"/>
            @endif
        </div>

        <div class="w-2/3 text-right">
            <div class="space-y-4">
                <div>
                    <h1 class="text-3xl font-light uppercase">{{ $document->header }}</h1>
                    @if ($document->subheader)
                        <p class="text-sm text-gray-600">{{ $document->subheader }}</p>
                    @endif
                </div>
                <div class="text-sm">
                    <strong class="text-sm block">{{ $document->company->name }}</strong>
                    @if($formattedAddress = $document->company->getFormattedAddressHtml())
                        {!! $formattedAddress !!}
                    @endif
                </div>
            </div>
        </div>
    </x-company.document-template.header>

    <x-company.document-template.metadata class="default-template-metadata space-y-4">
        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-sm">
                <h3 class="text-gray-600 font-medium mb-1">Rechnungsadresse</h3>
                <p class="text-sm font-bold">{{ $document->client?->name ?? 'Client Not Found' }}</p>
                @if($document->client && ($formattedAddress = $document->client->getFormattedAddressHtml()))
                    {!! $formattedAddress !!}
                @endif
            </div>

            <div class="text-sm">
                <table class="min-w-full">
                    <tbody>
                    <tr>
                        <td class="font-semibold text-right pr-2">Rechnungsnummer:</td>
                        <td class="text-left pl-2">{{ $document->number }}</td>
                    </tr>
                    @if($document->referenceNumber)
                        <tr>
                            <td class="font-semibold text-right pr-2">Rechnungsreferenz:</td>
                            <td class="text-left pl-2">{{ $document->referenceNumber }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="font-semibold text-right pr-2">Rechnungsdatum:</td>
                        <td class="text-left pl-2">{{ $document->date }}</td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-right pr-2">Fälligkeitsdatum:</td>
                        <td class="text-left pl-2">{{ $document->dueDate }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.document-template.metadata>

    <!-- Line Items Table -->
    <x-company.document-template.line-items class="default-template-line-items">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-relaxed" style="background: {{ $document->accentColor }}">
            <tr class="text-white">
                <th class="text-left pl-6 w-[50%] py-2">{{ $document->columnLabel->items }}</th>
                <th class="text-center w-[10%] py-2">{{ $document->columnLabel->units }}</th>
                <th class="text-right w-[20%] py-2">{{ $document->columnLabel->price }}</th>
                <th class="text-right pr-6 w-[20%] py-2">{{ $document->columnLabel->amount }}</th>
            </tr>
            </thead>
            <tbody class="text-sm border-b-2 border-gray-300">
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
            <tfoot class="text-sm summary-section">
            @if($document->subtotal)
                <tr>
                    <td class="pl-6 py-2" colspan="2"></td>
                    <td class="text-right font-semibold py-2">Subtotal:</td>
                    <td class="text-right pr-6 py-2">{{ $document->subtotal }}</td>
                </tr>
            @endif
            @if($document->discount)
                <tr class="text-success-800">
                    <td class="pl-6 py-2" colspan="2"></td>
                    <td class="text-right py-2">Discount:</td>
                    <td class="text-right pr-6 py-2">
                        ({{ $document->discount }})
                    </td>
                </tr>
            @endif
            @if($document->tax)
                <tr>
                    <td class="pl-6 py-2" colspan="2"></td>
                    <td class="text-right py-2">Tax:</td>
                    <td class="text-right pr-6 py-2">{{ $document->tax }}</td>
                </tr>
            @endif
            <tr>
                <td class="pl-6 py-2" colspan="2"></td>
                <td class="text-right font-semibold border-t py-2">{{ $document->amountDue ? 'Total' : 'Grand Total' }}:</td>
                <td class="text-right border-t pr-6 py-2">{{ $document->total }}</td>
            </tr>
            @if($document->amountDue)
                <tr>
                    <td class="pl-6 py-2" colspan="2"></td>
                    <td class="text-right font-semibold border-t-4 border-double py-2">Fälliger Betrag
                        ({{ $document->currencyCode }}):
                    </td>
                    <td class="text-right border-t-4 border-double pr-6 py-2">{{ $document->amountDue }}</td>
                </tr>
            @endif
            </tfoot>
        </table>
    </x-company.document-template.line-items>

    <!-- Footer Notes -->
    <x-company.document-template.footer class="default-template-footer flex flex-col text-sm p-6">
        @if($document->terms && $document->terms != "")
        <div>
            <h4 class="font-semibold mb-2">Terms & Conditions</h4>
            <p class="break-words line-clamp-4">{{ $document->terms }}</p>
        </div>
        @endif

        @if($document->footer)
            <div class="mt-auto text-center py-4">
                <p class="font-semibold">{{ $document->footer }}</p>
            </div>
        @endif
    </x-company.document-template.footer>
    <x-qr-slip
        :client="$document->client"
        :company="$document->company"
        :amount="$document->amountDue ?? $document->total"
        :currency="$document->currencyCode"
        :number="$document->number"
        :reference="$document->referenceNumber"
    />
</x-company.document-template.container>
