@props([
    'label' => null,
    'panelId' => null,
])

@php
    $targetId = \Illuminate\Support\Str::slug($label);
@endphp

<li class="grid grid-flow-col auto-cols-max gap-x-2 items-start p-2">
    <button
        x-ref="{{ $panelId }}-back"
        @click="goBack()"
        @keydown.enter="focusMenuItem('{{ $panelId }}-forward')"
        aria-label="Back"
        class="icon h-9 w-9 flex items-center justify-center rounded-full hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 cursor-pointer"
    >
        <x-filament::icon
            icon="heroicon-m-arrow-left"
            class="h-6 w-6 text-gray-600 dark:text-gray-200"
        />
    </button>
    <div class="px-2" aria-live="polite">
        <h1 class="text-gray-700 dark:text-gray-200 text-lg font-bold">
            {{ $label }}
        </h1>
    </div>
</li>
