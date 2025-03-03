@props([
    'icon' => null,
    'image' => null,
    'label' => null,
    'iconColor' => null,
    'url' => null,
])

@php
    $buttonClasses = \Illuminate\Support\Arr::toCssClasses([
        'text-gray-700 dark:text-gray-200 text-sm font-medium flex items-center p-2 rounded-lg hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
        'justify-between' => $icon && $label,
    ]);

    $iconClasses = \Illuminate\Support\Arr::toCssClasses([
        'h-6 w-6',
        match ($iconColor) {
            'gray' => 'text-gray-600 dark:text-gray-500',
            default => 'text-custom-500 dark:text-custom-400',
        },
    ]);

    $iconStyles = \Illuminate\Support\Arr::toCssStyles([
        \Filament\Support\get_color_css_variables(
            $iconColor,
            shades: [400, 500],
        ) => $iconColor !== 'gray',
    ]);

    $imageClasses = \Illuminate\Support\Arr::toCssClasses([
        'h-9 w-9 rounded-full bg-cover bg-center mr-4',
    ]);
@endphp
<li>
    <a
        href="{{ $url }}"
        {{
            $attributes
                ->only(['class'])
                ->class([$buttonClasses])
        }}
    >
        @if($image)
            <div class="{{ $imageClasses }}" style="background-image: url('{{ $image }}')"></div>
        @endif
        @if($label)
            <span class="flex-1">{{ $label }}</span>
        @endif
        @if($icon)
            <x-filament::icon
                :icon="$icon"
                :class="$iconClasses"
                :style="$iconStyles"
            />
        @endif
    </a>
</li>
