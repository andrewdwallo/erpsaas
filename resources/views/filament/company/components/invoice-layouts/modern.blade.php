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

<x-company.invoice.container class="modern-template-container" preview>

    <!-- Colored Header with Logo -->
    <x-company.invoice.header class="bg-gray-800 h-20">
        <!-- Logo -->
        <div class="w-2/3">
            @if($document->logo && $document->showLogo)
                <x-company.invoice.logo class="ml-6" :src="$document->logo"/>
            @endif
        </div>

        <!-- Ribbon Container -->
        <div class="w-1/3 absolute right-0 top-0 p-2 h-28 flex flex-col justify-end rounded-bl-sm"
             style="background: {{ $document->accentColor }};">
            @if($document->header)
                <h1 class="text-3xl font-bold text-white text-center uppercase">{{ $document->header }}</h1>
            @endif
        </div>
    </x-company.invoice.header>

    <!-- Company Details -->
    <x-company.invoice.metadata class="modern-template-metadata space-y-6">
        <div class="text-xs">
            <h2 class="text-base font-semibold">{{ $document->company->name }}</h2>
            @if($formattedAddress = $document->company->getFormattedAddressHtml())
                {!! $formattedAddress !!}
            @endif
        </div>

        <div class="flex justify-between items-end">
            <!-- Billing Details -->
            <div class="text-xs tracking-tight">
                <h3 class="text-gray-600 dark:text-gray-400 font-medium tracking-tight mb-1">BILL TO</h3>
                <p class="text-base font-bold"
                   style="color: {{ $document->accentColor }}">{{ $document->client->name }}</p>

                @if($formattedAddress = $document->client->getFormattedAddressHtml())
                    {!! $formattedAddress !!}
                @endif
            </div>

            <div class="text-xs tracking-tight">
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
    </x-company.invoice.metadata>

    <!-- Line Items Table -->
    <x-company.invoice.line-items class="modern-template-line-items">
        <table class="w-full text-left table-fixed">
            <thead class="text-sm leading-8">
            <tr class="text-gray-600 dark:text-gray-400">
                <th class="text-left pl-6 w-[50%]">{{ $document->columnLabel->items }}</th>
                <th class="text-center w-[10%]">{{ $document->columnLabel->units }}</th>
                <th class="text-right w-[20%]">{{ $document->columnLabel->price }}</th>
                <th class="text-right pr-6 w-[20%]">{{ $document->columnLabel->amount }}</th>
            </tr>
            </thead>
            <tbody class="text-xs tracking-tight border-y-2">
            @foreach($document->lineItems as $index => $item)
                <tr @class(['bg-gray-100 dark:bg-gray-800' => $index % 2 === 0])>
                    <td class="text-left pl-6 font-semibold py-2">
                        {{ $item->name }}
                        @if($item->description)
                            <div class="text-gray-600 font-normal line-clamp-2">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $item->unitPrice }}</td>
                    <td class="text-right pr-6">{{ $item->subtotal }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot class="text-xs tracking-tight">
            <tr>
                <td class="pl-6 py-1" colspan="2"></td>
                <td class="text-right font-semibold py-1">Subtotal:</td>
                <td class="text-right pr-6 py-1">{{ $document->subtotal }}</td>
            </tr>
            @if($document->discount)
                <tr class="text-success-800 dark:text-success-600">
                    <td class="pl-6 py-1" colspan="2"></td>
                    <td class="text-right py-1">Discount:</td>
                    <td class="text-right pr-6 py-1">
                        ({{ $document->discount }})
                    </td>
                </tr>
            @endif
            @if($document->tax)
                <tr>
                    <td class="pl-6 py-1" colspan="2"></td>
                    <td class="text-right py-1">Tax:</td>
                    <td class="text-right pr-6 py-1">{{ $document->tax }}</td>
                </tr>
            @endif
            <tr>
                <td class="pl-6 py-1" colspan="2"></td>
                <td class="text-right font-semibold border-t py-1">Total:</td>
                <td class="text-right border-t pr-6 py-1">{{ $document->total }}</td>
            </tr>
            @if($document->amountDue)
                <tr>
                    <td class="pl-6 py-1" colspan="2"></td>
                    <td class="text-right font-semibold border-t-4 border-double py-1">{{ $document->label->amountDue }}
                        ({{ $document->currencyCode }}):
                    </td>
                    <td class="text-right border-t-4 border-double pr-6 py-1">{{ $document->amountDue }}</td>
                </tr>
            @endif
            </tfoot>
        </table>
    </x-company.invoice.line-items>

    <!-- Footer Notes -->
    <x-company.invoice.footer class="modern-template-footer tracking-tight">
        <h4 class="font-semibold px-6" style="color: {{ $document->accentColor }}">Terms & Conditions</h4>
        <span class="border-t-2 my-2 border-gray-300 block w-full"></span>
        <div class="flex justify-between space-x-4 px-6">
            <p class="w-1/2 break-words line-clamp-4">{{ $document->terms }}</p>
            <p class="w-1/2 break-words line-clamp-4">{{ $document->footer }}</p>
        </div>
    </x-company.invoice.footer>
</x-company.invoice.container>
