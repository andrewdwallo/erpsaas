@props([
    'heading' => null,
    'description' => null,
    'icon' => null,
    'iconColor' => 'gray',
])

<div class="group relative p-6 hover:bg-gray-300/5 dark:hover:bg-gray-700/5">
    <span
        @class([
            'inline-flex rounded-lg p-3 ring-4 ring-white dark:ring-gray-900',
            match ($iconColor) {
                'gray' => 'fi-color-gray bg-gray-50 text-gray-700 dark:bg-gray-900 dark:text-gray-500',
                default => 'fi-color-custom bg-custom-50 text-custom-700 dark:bg-custom-950 dark:text-custom-500',
            },
        ])
        @style([
            \Filament\Support\get_color_css_variables(
                $iconColor,
                shades: [50, 500, 700, 950],
            ) => $iconColor !== 'gray',
        ])
    >
        <x-filament::icon :icon="$icon" class="h-6 w-6" />
    </span>
    <div class="mt-8 pr-1">
        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
            {{ $heading }}
        </h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $description }}
        </p>
    </div>
    <x-filament::icon
        icon="heroicon-o-arrow-up-right"
        class="absolute right-6 top-6 text-gray-300 group-hover:text-gray-400 h-6 w-6"
    />
</div>
