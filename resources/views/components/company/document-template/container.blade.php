@props([
    'preview' => false,
])

<div
    @class([
        'doc-template-container flex justify-center',
    ])
>
    <div class="max-w-full overflow-x-auto shadow-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        <div
            @class([
                'doc-template-paper bg-[#ffffff] overflow-y-auto',
                'w-205 h-256' => ! $preview,
                'w-3xl min-h-247 preview' => $preview,
            ])
        >
            {{ $slot }}
        </div>
    </div>
</div>
