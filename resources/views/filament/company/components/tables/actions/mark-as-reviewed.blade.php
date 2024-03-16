@php
    $isDisabled = $action->isDisabled();
@endphp

<span
    x-data="{}"
    x-tooltip="{
        content: @js($action->getTooltip()),
        theme: $store.theme === 'dark' ? 'light' : 'dark',
        placement: 'left',
    }"
>
    <x-filament::icon-button
        @class([
            'disabled:cursor-not-allowed disabled:pointer-events-auto disabled:hover:text-gray-400',
        ])
        :icon="$action->getIcon()"
        :color="$action->getColor()"
        :disabled="$isDisabled"
        :size="$action->getIconSize()"
        :wire:click="$action->getLivewireClickHandler()"
    />
</span>
