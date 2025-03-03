@props([
    'reportLoaded' => false,
])

<x-filament-tables::container>
    <div class="es-table__header-ctn"></div>
    <div
        class="relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10 min-h-40">
        <div wire:init="applyFilters" class="relative min-h-40">
            <div wire:loading.class="flex items-center justify-center w-full min-h-40 absolute inset-0 z-10">
                <div wire:loading wire:target="applyFilters">
                    <x-filament::loading-indicator class="p-5 text-primary-700 dark:text-primary-300"/>
                </div>
            </div>

            @if($reportLoaded)
                <div wire:loading.remove wire:target="applyFilters">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
    <div class="es-table__footer-ctn border-t border-gray-200"></div>
</x-filament-tables::container>
