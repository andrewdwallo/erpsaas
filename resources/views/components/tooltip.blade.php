@props([
    'text' => null,
    'icon' => null,
    'placement' => 'right',
    'maxWidth' => 300,
])

<div class="es-tooltip" x-data="{ open: false }">
    <span
        x-ref="trigger"
        @click="open = !open"
        @click.away="open = false"
        x-tooltip="{
            content: () => $refs.tooltipContent ? $refs.tooltipContent.innerHTML : '',
            trigger: 'click',
            appendTo: $root,
            allowHTML: true,
            interactive: true,
            theme: $store.theme,
            placement: '{{ $placement }}',
            maxWidth: {{ $maxWidth }},
        }">
        <x-filament::icon-button
            :icon="$icon"
            class="w-5 h-5 text-gray-400 hover:text-primary-600 focus-visible:ring-primary-600 dark:text-gray-500 dark:hover:text-primary-300 dark:focus-visible:ring-primary-500"
        />
    </span>
    <template x-ref="tooltipContent">
        <div class="es-tooltip-content-wrapper py-4 px-5">
            <button @click="$refs.trigger.click()" class="es-close-tooltip"></button>
            <div class="es-tooltip-content">
                <p class="es-tooltip-text text-sm font-normal text-gray-800 dark:text-gray-200">
                    {{ $text }}
                </p>
            </div>
        </div>
    </template>
</div>
