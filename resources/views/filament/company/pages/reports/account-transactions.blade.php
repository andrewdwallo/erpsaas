<x-filament-panels::page>
    <x-filament::section>
        @if(method_exists($this, 'filtersForm'))
            {{ $this->filtersForm }}
        @endif
    </x-filament::section>

    <x-company.tables.container :report-loaded="$this->reportLoaded">
        @if(! $this->tableHasEmptyState())
            <x-company.tables.reports.account-transactions :report="$this->report"/>
        @else
            <div class="fi-ta-empty-state">
                <div class="fi-ta-empty-state-content">
                    <div class="fi-ta-empty-state-icon-bg">
                        {{ \Filament\Support\generate_icon_html($this->getEmptyStateIcon(), size: \Filament\Support\Enums\IconSize::Large) }}
                    </div>

                    <h4 class="fi-ta-empty-state-heading">
                        {{ $this->getEmptyStateHeading() }}
                    </h4>

                    @if(filled($emptyStateDescription = $this->getEmptyStateDescription()))
                        <p class="fi-ta-empty-state-description">
                            {{ $emptyStateDescription }}
                        </p>
                    @endif

                    @if($emptyStateActions = array_filter(
                        $this->getEmptyStateActions(),
                        fn (\Filament\Actions\Action | \Filament\Actions\ActionGroup $action): bool => $action->isVisible(),
                    ))
                        <div class="fi-ta-actions fi-align-center fi-wrapped">
                            @foreach($emptyStateActions as $action)
                                {{ $action }}
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </x-company.tables.container>
</x-filament-panels::page>
