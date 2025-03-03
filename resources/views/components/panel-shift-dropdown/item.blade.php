@props([
    'url' => null,
    'icon' => null,
    'image' => null,
    'label' => null,
    'tag' => 'a',
])

@php
    $buttonClasses = \Illuminate\Support\Arr::toCssClasses([
        'text-gray-700 dark:text-gray-200 text-sm font-medium flex items-center p-2 rounded-lg hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5',
        'w-full' => $tag === 'form',
    ]);

    $iconWrapperClasses = \Illuminate\Support\Arr::toCssClasses([
        'icon h-9 w-9 flex justify-center items-center mr-4 rounded-full bg-gray-200 dark:bg-white/10',
    ]);

    $iconClasses = \Illuminate\Support\Arr::toCssClasses([
        'h-6 w-6 text-gray-600 dark:text-gray-200',
    ]);

    $imageClasses = \Illuminate\Support\Arr::toCssClasses([
        'h-9 w-9 rounded-full bg-cover bg-center mr-4',
    ]);
@endphp
<li>
    @if($tag === 'form')
        <form
            {{ $attributes->only(['action', 'method', 'wire:submit']) }}
        >
            @csrf

            <button
                type="submit"
                {{
                    $attributes
                        ->only(['class'])
                        ->class([$buttonClasses])
                }}
            >
                @if($image)
                    <div class="{{ $imageClasses }}" style="background-image: url('{{ $image }}')"></div>
                @else
                    <div class="{{ $iconWrapperClasses }}">
                        <x-filament::icon
                            :icon="$icon ?? 'heroicon-m-document-text'"
                            :class="$iconClasses"
                        />
                    </div>
                @endif
                @if($label)
                    <span>{{ $label }}</span>
                @endif
            </button>
        </form>
    @else
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
            @else
                <div class="{{ $iconWrapperClasses }}">
                    <x-filament::icon
                        :icon="$icon ?? 'heroicon-m-document-text'"
                        :class="$iconClasses"
                    />
                </div>
            @endif
            @if($label)
                <span>{{ $label }}</span>
            @endif
        </a>
    @endif
</li>
