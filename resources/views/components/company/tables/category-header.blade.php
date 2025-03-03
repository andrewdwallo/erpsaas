@props([
    'categoryHeaders',
    'alignmentClass' => null,
])


<tr class="bg-gray-50 dark:bg-white/5">
    @foreach($categoryHeaders as $index => $header)
        <th
            @class([
                'px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6',
                $alignmentClass($index) => $alignmentClass,
            ])
        >
            <span class="text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                {{ $header }}
            </span>
        </th>
    @endforeach
</tr>
