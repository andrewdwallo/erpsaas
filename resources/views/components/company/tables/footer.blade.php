@props(['totals', 'alignmentClass'])

@if(!empty($totals))
    <tfoot>
    <tr class="bg-gray-50 dark:bg-white/5">
        @foreach($totals as $totalIndex => $totalCell)
            <x-filament-tables::cell class="{{ $alignmentClass($totalIndex) }}">
                <div class="px-3 py-3.5 text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $totalCell }}
                </div>
            </x-filament-tables::cell>
        @endforeach
    </tr>
    </tfoot>
@endif
