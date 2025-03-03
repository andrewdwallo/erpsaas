@props([
    'reportLoaded' => false,
    'summaryData' => [],
    'targetLabel' => null,
])

<div>
    <x-filament::section>
        @if($reportLoaded)
            <div class="flex flex-col md:flex-row items-center md:items-end text-center justify-center gap-4 md:gap-8">
                @foreach($summaryData as $summary)
                    <div class="text-sm">
                        <div class="text-gray-600 dark:text-gray-200 font-medium mb-2">{{ $summary['label'] }}</div>

                        @php
                            $isTargetLabel = $summary['label'] === $targetLabel;
                            $isPositive = money($summary['value'], \App\Utilities\Currency\CurrencyAccessor::getDefaultCurrency())->isPositive();
                        @endphp

                        <strong
                            @class([
                                'text-lg',
                                'text-success-700 dark:text-success-400' => $isTargetLabel && $isPositive,
                                'text-danger-700 dark:text-danger-400' => $isTargetLabel && ! $isPositive,
                            ])
                        >
                            {{ $summary['value'] }}
                        </strong>
                    </div>

                    @if(! $loop->last)
                        <div class="flex items-center justify-center px-2">
                            <strong class="text-lg">
                                {{ $loop->remaining === 1 ? '=' : '-' }}
                            </strong>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </x-filament::section>
</div>
