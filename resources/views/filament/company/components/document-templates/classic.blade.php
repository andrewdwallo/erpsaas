<x-company.document-template.container class="classic-template-container" :preview="$preview">
    <!-- Header Section -->
    <x-company.document-template.header class="classic-template-header">
        <div class="w-2/3 text-left">
            <div class="text-sm">
                <strong class="text-sm block">{{ $document->company->name }}</strong>
                @if($formattedAddress = $document->company->getFormattedAddressHtml())
                    {!! $formattedAddress !!}
                @endif
            </div>
        </div>

        <div class="w-1/3 flex justify-end">
            @if($document->logo && $document->showLogo)
                <x-company.document-template.logo :src="$document->logo"/>
            @endif
        </div>
    </x-company.document-template.header>

    <x-company.document-template.metadata class="classic-template-metadata space-y-4">
        <div class="items-center flex">
            <hr class="grow-2 py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
            <x-icons.document-header-decoration
                color="{{ $document->accentColor }}"
                text="{{ $document->header }}"
                class="w-60"
            />
            <hr class="grow-2 py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
        </div>
        @if ($document->subheader)
            <p class="text-sm text-center text-gray-600">{{ $document->subheader }}</p>
        @endif

        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-sm">
                <h3 class="text-gray-600 font-medium mb-1">BILL TO</h3>
                <p class="text-sm font-bold">{{ $document->client?->name ?? 'Client Not Found' }}</p>
                @if($document->client && ($formattedAddress = $document->client->getFormattedAddressHtml()))
                    {!! $formattedAddress !!}
                @endif
            </div>

            <div class="text-sm">
                <table class="min-w-full">
                    <tbody>
                    <tr>
                        <td class="font-semibold text-right pr-2">{{ $document->label->number }}:</td>
                        <td class="text-left pl-2">{{ $document->number }}</td>
                    </tr>
                    @if($document->referenceNumber)
                        <tr>
                            <td class="font-semibold text-right pr-2">{{ $document->label->referenceNumber }}:</td>
                            <td class="text-left pl-2">{{ $document->referenceNumber }}</td>
                        </tr>
                    @endif
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
    </x-company.document-template.metadata>

    <!-- Line Items -->
    <x-company.document-template.line-items class="classic-template-line-items px-6">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-relaxed">
            <tr>
                <th class="text-left w-[50%] py-4">{{ $document->columnLabel->items }}</th>
                <th class="text-center w-[10%] py-4">{{ $document->columnLabel->units }}</th>
                <th class="text-right w-[20%] py-4">{{ $document->columnLabel->price }}</th>
                <th class="text-right w-[20%] py-4">{{ $document->columnLabel->amount }}</th>
            </tr>
            </thead>
            <tbody class="text-sm border-y-2 border-dotted border-gray-300">
            @foreach($document->lineItems as $item)
                <tr>
                    <td class="text-left font-semibold py-3">
                        {{ $item->name }}
                        @if($item->description)
                            <div class="text-gray-600 font-normal line-clamp-2 mt-1">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center py-3">{{ $item->quantity }}</td>
                    <td class="text-right py-3">{{ $item->unitPrice }}</td>
                    <td class="text-right py-3">{{ $item->subtotal }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Financial Details and Notes -->
        <div class="flex justify-between text-sm space-x-1 pt-4">
            <!-- Notes Section -->
            <div class="w-[60%] py-2">
                <p class="font-semibold">{{ $document->footer }}</p>
            </div>

            <!-- Financial Summary -->
            <div class="w-[40%]">
                <table class="w-full table-fixed whitespace-nowrap">
                    <tbody class="text-sm">
                    @if($document->subtotal)
                        <tr>
                            <td class="text-right font-semibold py-2">Subtotal:</td>
                            <td class="text-right py-2">{{ $document->subtotal }}</td>
                        </tr>
                    @endif
                    @if($document->discount)
                        <tr class="text-success-800">
                            <td class="text-right py-2">Discount:</td>
                            <td class="text-right py-2">
                                ({{ $document->discount }})
                            </td>
                        </tr>
                    @endif
                    @if($document->tax)
                        <tr>
                            <td class="text-right py-2">Tax:</td>
                            <td class="text-right py-2">{{ $document->tax }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-right font-semibold border-t py-2">{{ $document->amountDue ? 'Total' : 'Grand Total' }}:</td>
                        <td class="text-right border-t py-2">{{ $document->total }}</td>
                    </tr>
                    @if($document->amountDue)
                        <tr>
                            <td class="text-right font-semibold border-t-4 border-double py-2">{{ $document->label->amountDue }}
                                ({{ $document->currencyCode }}):
                            </td>
                            <td class="text-right border-t-4 border-double py-2">{{ $document->amountDue }}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.document-template.line-items>

    <!-- Footer -->
    <x-company.document-template.footer class="classic-template-footer p-6 text-sm">
        <h4 class="font-semibold mb-2">Terms & Conditions</h4>
        <p class="break-words line-clamp-4">{{ $document->terms }}</p>
    </x-company.document-template.footer>
</x-company.document-template.container>
