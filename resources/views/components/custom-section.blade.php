@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\IconSize;
@endphp

@props([
    'aside' => false,
    'collapsed' => false,
    'collapsible' => false,
    'compact' => false,
    'contained' => true,
    'contentBefore' => false,
    'description' => null,
    'footerActions' => [],
    'footerActionsAlignment' => Alignment::Start,
    'headerActions' => [],
    'headerEnd' => null,
    'heading' => null,
    'icon' => null,
    'iconColor' => 'gray',
    'iconSize' => IconSize::Large,
    'persistCollapsed' => false,
])

@php
    $hasDescription = filled((string) $description);
    $hasHeading = filled($heading);
    $hasIcon = filled($icon);

    if (is_array($headerActions)) {
        $headerActions = array_filter(
            $headerActions,
            fn ($headerAction): bool => $headerAction->isVisible(),
        );
    }

    if (is_array($footerActions)) {
        $footerActions = array_filter(
            $footerActions,
            fn ($footerAction): bool => $footerAction->isVisible(),
        );
    }

    $hasHeaderActions = $headerActions instanceof \Illuminate\Contracts\Support\Htmlable
        ? ! \Filament\Support\is_slot_empty($headerActions)
        : filled($headerActions);

    $hasFooterActions = $footerActions instanceof \Illuminate\Contracts\Support\Htmlable
        ? ! \Filament\Support\is_slot_empty($footerActions)
        : filled($footerActions);

    $hasHeader = $hasIcon || $hasHeading || $hasDescription || $collapsible || $hasHeaderActions || filled((string) $headerEnd);
@endphp

<section
    {{-- TODO: Investigate Livewire bug - https://github.com/filamentphp/filament/pull/8511 --}}
    x-data="{
        isCollapsed: @if ($persistCollapsed) $persist(@js($collapsed)).as(`section-${$el.id}-isCollapsed`) @else @js($collapsed) @endif,
    }"
    @if ($collapsible)
        x-on:collapse-section.window="if ($event.detail.id == $el.id) isCollapsed = true"
        x-on:expand="isCollapsed = false"
        x-on:open-section.window="if ($event.detail.id == $el.id) isCollapsed = false"
        x-on:toggle-section.window="if ($event.detail.id == $el.id) isCollapsed = ! isCollapsed"
        x-bind:class="isCollapsed && 'fi-collapsed'"
    @endif
    {{
        $attributes->class([
            'fi-custom-section',
            'fi-section-not-contained' => ! $contained,
            'fi-section-has-content-before' => $contentBefore,
            'fi-section-has-header' => $hasHeader,
            'fi-aside' => $aside,
            'fi-compact' => $compact,
            'fi-collapsible' => $collapsible,
        ])
    }}
>
    @if ($hasHeader)
        <header
            @if ($collapsible)
                x-on:click="isCollapsed = ! isCollapsed"
            @endif
            class="fi-section-header"
        >
            <div class="flex items-center gap-3">
                @if ($hasIcon)
                    <x-filament::icon
                        :icon="$icon"
                        @class([
                            'fi-section-header-icon',
                            match ($iconColor) {
                                'gray' => null,
                                default => 'fi-color-custom',
                            },
                            is_string($iconColor) ? "fi-color-{$iconColor}" : null,
                            ($iconSize instanceof IconSize) ? "fi-size-{$iconSize->value}" : (is_string($iconSize) ? $iconSize : null),
                        ])
                        @style([
                            \Filament\Support\get_color_css_variables(
                                $iconColor,
                                shades: [400, 500],
                                alias: 'section.header.icon',
                            ) => $iconColor !== 'gray',
                        ])
                    />
                @endif

                @if ($hasHeading || $hasDescription)
                    <div class="fi-section-header-text-ctn">
                        @if ($hasHeading)
                            <x-filament::section.heading>
                                {{ $heading }}
                            </x-filament::section.heading>
                        @endif

                        @if ($hasDescription)
                            <x-filament::section.description>
                                {{ $description }}
                            </x-filament::section.description>
                        @endif
                    </div>
                @endif

                @if ($hasHeaderActions)
                    <div class="hidden sm:block">
                        <x-filament::actions
                            :actions="$headerActions"
                            :alignment="\Filament\Support\Enums\Alignment::Start"
                            x-on:click.stop=""
                        />
                    </div>
                @endif

                {{ $headerEnd }}

                @if ($collapsible)
                    <x-filament::icon-button
                        color="gray"
                        icon="heroicon-m-chevron-down"
                        icon-alias="section.collapse-button"
                        x-on:click.stop="isCollapsed = ! isCollapsed"
                        x-bind:class="{ 'rotate-180': ! isCollapsed }"
                    />
                @endif
            </div>

            @if ($hasHeaderActions)
                <div class="sm:hidden">
                    <x-filament::actions
                        :actions="$headerActions"
                        :alignment="\Filament\Support\Enums\Alignment::Start"
                        x-on:click.stop=""
                    />
                </div>
            @endif
        </header>
    @endif

    <div
        @if ($collapsible)
            x-bind:aria-expanded="(! isCollapsed).toString()"
            @if ($collapsed || $persistCollapsed)
                x-cloak
            @endif
        @endif
        class="fi-section-content-ctn"
    >
        <div class="fi-section-content">
            {{ $slot }}
        </div>

        @if ($hasFooterActions)
            <footer class="fi-section-footer">
                <x-filament::actions
                    :actions="$footerActions"
                    :alignment="$footerActionsAlignment"
                />
            </footer>
        @endif
    </div>
</section>
