@props([
    'activeTab' => 'summary',
    'tabs' => ['summary' => 'Summary', 'details' => 'Details'],
])

<x-filament::tabs>
    @foreach ($tabs as $tabKey => $label)
        <x-filament::tabs.item
            :active="$activeTab === $tabKey"
            wire:click="$set('activeTab', '{{ $tabKey }}')"
        >
            {{ $label }}
        </x-filament::tabs.item>
    @endforeach
</x-filament::tabs>
