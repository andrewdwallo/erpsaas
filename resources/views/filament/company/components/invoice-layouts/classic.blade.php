@php
    $data = $this->form->getRawState();
    $document = \App\DTO\DocumentPreviewDTO::fromSettings($this->record, $data);
@endphp

{!! $document->getFontHtml() !!}

<style>
    .inv-paper {
        font-family: '{{ $document->font->getLabel() }}', sans-serif;
    }
</style>

<x-company.invoice.container class="classic-template-container" preview>
    <!-- Header Section -->
    <x-company.invoice.header class="default-template-header">
        <div class="w-2/3 text-left ml-6">
            <div class="text-xs">
                <h2 class="text-base font-semibold">{{ $document->company->name }}</h2>
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

    <x-company.invoice.metadata class="classic-template-metadata">
        <div class="items-center flex">
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
            <x-icons.document-header-decoration
                color="{{ $document->accentColor }}"
                text="{{ $document->header }}"
                class="w-48"
            />
            <hr class="grow-[2] py-0.5 border-solid border-y-2" style="border-color: {{ $document->accentColor }};">
        </div>
        <div class="mt-2 text-sm text-center text-gray-600 dark:text-gray-400">{{ $document->subheader }}</div>

        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-xs">
                <h3 class="text-gray-600 dark:text-gray-400 font-medium tracking-tight mb-1">BILL TO</h3>
                <p class="text-base font-bold">{{ $document->client->name }}</p>
                @if($formattedAddress = $document->client->getFormattedAddressHtml())
                    {!! $formattedAddress !!}
                @endif
            </div>

            <div class="text-xs">
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
    <x-company.invoice.line-items class="classic-template-line-items px-6">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-8">
            <tr>
                <th class="text-left">{{ $document->columnLabel->items }}</th>
                <th class="text-center">{{ $document->columnLabel->units }}</th>
                <th class="text-right">{{ $document->columnLabel->price }}</th>
                <th class="text-right">{{ $document->columnLabel->amount }}</th>
            </tr>
            </thead>
            <tbody class="text-xs border-t-2 border-b-2 border-dotted border-gray-300 leading-8">
            @foreach($document->lineItems as $item)
                <tr>
                    <td class="text-left font-semibold">{{ $item->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $item->unitPrice }}</td>
                    <td class="text-right">{{ $item->subtotal }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Financial Details and Notes -->
        <div class="flex justify-between text-xs space-x-1">
            <!-- Notes Section -->
            <div class="w-1/2 border border-dashed border-gray-300 p-2 mt-4">
                <h4 class="font-semibold mb-2">Notes</h4>
                <p>{{ $document->footer }}</p>
            </div>

            <!-- Financial Summary -->
            <div class="w-1/2 mt-2">
                <table class="w-full table-fixed">
                    <tbody class="text-xs leading-loose">
                    <tr>
                        <td class="text-right font-semibold">Subtotal:</td>
                        <td class="text-right">{{ $document->subtotal }}</td>
                    </tr>
                    <tr class="text-success-800 dark:text-success-600">
                        <td class="text-right">Discount (5%):</td>
                        <td class="text-right">({{ $document->discount }})</td>
                    </tr>
                    <tr>
                        <td class="text-right">Sales Tax (10%):</td>
                        <td class="text-right">{{ $document->tax }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-semibold">Total:</td>
                        <td class="text-right">{{ $document->total }}</td>
                    </tr>
                    <tr>
                        <td class="text-right font-semibold">Amount Due (USD):</td>
                        <td class="text-right">{{ $document->amountDue }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-company.invoice.line-items>

    <!-- Footer -->
    <x-company.invoice.footer class="classic-template-footer">
        <h4 class="font-semibold px-6 mb-2">Terms & Conditions</h4>
        <p class="px-6 break-words line-clamp-4">{{ $document->terms }}</p>
    </x-company.invoice.footer>
</x-company.invoice.container>
