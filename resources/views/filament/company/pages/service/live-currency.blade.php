<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'currency-list'"
            wire:click="$set('activeTab', 'currency-list')"
            :badge="$currencyListQuery"
        >
            {{ translate('Currency List') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'company-currencies'"
            wire:click="$set('activeTab', 'company-currencies')"
            :badge="$companyCurrenciesQuery"
        >
            {{ translate('Company Currencies') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    @if($activeTab === 'currency-list')
        @livewire('company.service.live-currency.list-currencies')
    @elseif($activeTab === 'company-currencies')
        @livewire('company.service.live-currency.list-company-currencies')
    @endif
</x-filament-panels::page>
