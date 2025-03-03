<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between gap-4">
            <!-- Form Container -->
            @if(method_exists($this, 'filtersForm'))
                {{ $this->filtersForm }}
            @endif

            <!-- Grouping Button and Column Toggle -->
            @if($this->hasToggleableColumns())
                <div class="lg:mb-1">
                    <x-filament-tables::column-toggle.dropdown
                        :form="$this->getTableColumnToggleForm()"
                        :trigger-action="$this->getToggleColumnsTriggerAction()"
                    />
                </div>
            @endif

            <div class="inline-flex items-center min-w-0 lg:min-w-[9.5rem] justify-end">
                {{ $this->applyFiltersAction }}
            </div>
        </div>
    </x-filament::section>

    <x-report-summary-section
        :report-loaded="$this->reportLoaded"
        :summary-data="$this->report?->getSummary()"
        target-label="Net Assets"
    />

    <x-report-tabs :active-tab="$activeTab" :tabs="$this->getTabs()"/>

    <x-company.tables.container :report-loaded="$this->reportLoaded">
        @if($this->report)
            @if($activeTab === 'summary')
                <x-company.tables.reports.balance-sheet-summary :report="$this->report"/>
            @elseif($activeTab === 'details')
                <x-company.tables.reports.balance-sheet :report="$this->report"/>
            @endif
        @endif
    </x-company.tables.container>
</x-filament-panels::page>

