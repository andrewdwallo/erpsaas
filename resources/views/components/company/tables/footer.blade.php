@props(['totals', 'alignmentClass'])

@if(!empty($totals))
    <tfoot>
    <tr class="bg-gray-50 dark:bg-white/5">
        @foreach($totals as $totalIndex => $totalCell)
            <td class="fi-ta-cell {{ $alignmentClass($totalIndex) }}">
                <div class="px-3 py-3.5 text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $totalCell }}
                </div>
            </td>
        @endforeach
    </tr>
    </tfoot>
@endif
