@props([
    'alignmentClass',
    'indent' => false,
    'bold' => false,
    'underlineThin' => false,
    'underlineBold' => false,
])

<td
    @class([
        $alignmentClass,
        'last-of-type:pe-1 sm:last-of-type:pe-3',
        'ps-4 sm:first-of-type:ps-7' => $indent,
        'p-0 first-of-type:ps-1 sm:first-of-type:ps-3' => ! $indent,
    ])
>
    <div
        @class([
            'px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white',
            'font-semibold' => $bold,
            'border-b border-gray-700 dark:border-white/10' => $underlineThin,
            'border-b-[1.5px] border-gray-800 dark:border-white/5' => $underlineBold,
        ])
    >
        {{ $slot }}
    </div>
</td>
