@props([
    'panelId' => null,
])

<style>
    .transition-class {
        transition-property: transform, opacity;
        transition-duration: 0.2s;
        transition-timing-function: ease-in-out;
    }

    .hide {
        display: none;
    }
</style>

<ul
    x-ref="{{ $panelId }}"
    class="w-full p-2.5 list-none flex flex-col space-y-2 hide transition-class"
>
    {{ $slot }}
</ul>
