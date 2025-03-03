@props([
    'label' => null,
    'icon' => null,
    'panelId' => null,
])

<li>
    <button
        x-ref="{{ $panelId }}-forward"
        @click="setActiveMenu('{{ $panelId }}')"
        @keydown.enter="focusBackButton('{{ $panelId }}-back')"
        aria-label="Go to {{ $label }}"
        class="w-full text-gray-700 dark:text-gray-200 text-sm font-medium flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5">
        <div class="flex items-center">
            <div class="icon h-9 w-9 flex justify-center items-center mr-4 rounded-full bg-gray-200 dark:bg-white/10">
                @if($icon)
                    <x-filament::icon :icon="$icon" class="h-6 w-6 text-gray-600 dark:text-gray-200"/>
                @endif
            </div>
            @if($label)
                <span>{{ $label }}</span>
            @endif
        </div>
        <x-filament::icon icon="heroicon-m-chevron-right" class="text-gray-300 h-8 w-8 pointer-events-none"/>
    </button>
</li>
