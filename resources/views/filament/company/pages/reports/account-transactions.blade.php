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
            <x-filament-tables::empty-state
                :actions="$this->getEmptyStateActions()"
                :description="$this->getEmptyStateDescription()"
                :heading="$this->getEmptyStateHeading()"
                :icon="$this->getEmptyStateIcon()"
            />
        @endif
    </x-company.tables.container>
</x-filament-panels::page>
