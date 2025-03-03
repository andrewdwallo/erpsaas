@props([
    'headers',
    'alignmentClass',
])

<thead class="divide-y divide-gray-200 dark:divide-white/5 whitespace-nowrap">
<tr class="bg-gray-50 dark:bg-white/5">
    @foreach($headers as $headerIndex => $headerCell)
        <th class="px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 {{ $alignmentClass($headerIndex) }}">
            <span class="text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                {{ $headerCell }}
            </span>
        </th>
    @endforeach
</tr>
</thead>
