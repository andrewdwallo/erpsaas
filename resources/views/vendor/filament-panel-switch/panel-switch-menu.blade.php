@php
    $getUrlScheme = (string) app()->environment('production') ? 'https://' : 'http://';

    $getPanelPath = fn (\Filament\Panel $panel): string => filled($domains = $panel->getDomains())
            ? str(collect($domains)->first())->prepend($getUrlScheme)->toString()
            : str($panel->getPath())->prepend('/')->toString();

    $getHref = fn (\Filament\Panel $panel): ?string => $canSwitchPanels && $panel->getId() !== $currentPanel->getId()
            ? $getPanelPath($panel)
            : null;
@endphp

@if ($isSimple)
    <x-filament::dropdown teleport placement="bottom-end">
        <x-slot name="trigger">
            <div x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="flex items-center justify-center gap-x-2 rounded-lg px-3 py-2 text-sm font-semibold outline-none transition duration-75 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 text-gray-700 dark:text-gray-200">
                    <span class="ml-4">{{  $labels[$currentPanel->getId()] ?? str($currentPanel->getId())->ucfirst() }}</span>
                    <x-heroicon-m-chevron-down x-show="!open" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                    <x-heroicon-m-chevron-up x-show="open" x-cloak class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                </button>
            </div>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($panels as $panel)
                <x-filament::dropdown.list.item
                    :href="$getHref($panel)"
                    :icon="$icons[$panel->getId()] ?? 'heroicon-s-square-2-stack'"
                    tag="a"
                >
                    {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst() }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@else
    <style>
        .panel-switch-modal .fi-modal-content {
            align-items: center !important;
            justify-content: center !important;
        }
    </style>
    <x-filament::icon-button
        x-data="{}"
        icon="heroicon-o-square-3-stack-3d"
        icon-alias="panels::panel-switch-modern-icon"
        icon-size="lg"
        @click="$dispatch('open-modal', { id: 'panel-switch' })"
        label="Switch Panels"
        class="text-gray-700 dark:text-primary-500"
    />

    <x-filament::modal
        id="panel-switch"
        :width="$modalWidth"
        alignment="center"
        display-classes="block"
        :slide-over="$isSlideOver"
        :sticky-header="$isSlideOver"
        :heading="$heading"
        class="panel-switch-modal"
    >
        <div
            class="flex flex-wrap items-center justify-center gap-4 md:gap-6"
        >
            @foreach ($panels as $panel)
                <!-- x-on:click="location.href = '{{ $getHref($panel) }}'" -->
                <a
                    href="{{ $getHref($panel) }}"
                    class="flex flex-col items-center justify-center flex-1 hover:cursor-pointer group panel-switch-card"
                >
                    <div
                        @class([
                            "p-2 bg-white rounded-lg shadow-md dark:bg-gray-800 panel-switch-card-section",
                            "group-hover:ring-2 group-hover:ring-primary-600" => $panel->getId() !== $currentPanel->getId(),
                            "ring-2 ring-primary-600" => $panel->getId() === $currentPanel->getId(),
                        ])
                    >
                        @if ($renderIconAsImage)
                            <img
                                class="rounded-lg panel-switch-card-image"
                                style="width: {{ $iconSize * 4 }}px; height: {{ $iconSize * 4 }}px;"
                                src="{{ $icons[$panel->getId()] ?? 'https://raw.githubusercontent.com/bezhanSalleh/filament-panel-switch/3.x/art/banner.jpg' }}"
                                alt="Panel Image"
                            >
                        @else
                            @php
                                $iconName = $icons[$panel->getId()] ?? 'heroicon-s-square-2-stack' ;
                            @endphp
                            @svg($iconName, 'text-primary-600 panel-switch-card-icon', ['style' => 'width: ' . ($iconSize * 4) . 'px; height: ' . ($iconSize * 4). 'px;'])
                        @endif
                    </div>
                    <span
                        @class([
                            "mt-2 text-sm font-medium text-center text-gray-400 dark:text-gray-200 break-words panel-switch-card-title",
                            "text-gray-400 dark:text-gray-200 group-hover:text-primary-600 group-hover:dark:text-primary-400" => $panel->getId() !== $currentPanel->getId(),
                            "text-primary-600 dark:text-primary-400" => $panel->getId() === $currentPanel->getId(),
                        ])
                    >
                        {{ $labels[$panel->getId()] ?? str($panel->getId())->ucfirst()}}
                    </span>
                </a>
            @endforeach
        </div>
    </x-filament::modal>
@endif
