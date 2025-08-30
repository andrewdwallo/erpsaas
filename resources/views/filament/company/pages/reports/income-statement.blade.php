<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
            <!-- Form Container -->
            @if(method_exists($this, 'filtersForm'))
                <div class="flex-1 min-w-0">
                    {{ $this->filtersForm }}
                </div>
            @endif

            <!-- Grouping Button and Column Toggle -->
            @if($this->hasToggleableColumns())
                <x-company.tables.column-toggle.dropdown
                    :form="$this->getTableColumnToggleForm()"
                    :trigger-action="$this->getToggleColumnsTriggerAction()"
                />
            @endif

            <div class="shrink-0 w-38 flex justify-end">
                {{ $this->applyFiltersAction }}
            </div>
        </div>
    </x-filament::section>

    <x-report-summary-section
        :report-loaded="$this->reportLoaded"
        :summary-data="$this->report?->getSummary()"
        target-label="Net Earnings"
    />

    <x-report-tabs :active-tab="$activeTab" :tabs="$this->getTabs()"/>

    <x-company.tables.container :report-loaded="$this->reportLoaded">
        @if($this->report)
            @if($activeTab === 'summary')
                <x-company.tables.reports.income-statement-summary :report="$this->report"/>
            @elseif($activeTab === 'details')
                <x-company.tables.reports.income-statement :report="$this->report"/>
            @endif
        @endif
    </x-company.tables.container>
</x-filament-panels::page>
