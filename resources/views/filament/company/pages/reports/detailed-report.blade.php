<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <!-- Form Container -->
            @if(method_exists($this, 'filtersForm'))
                {{ $this->filtersForm }}
            @endif

            <!-- Grouping Button and Column Toggle -->
            @if($this->hasToggleableColumns())
                <x-company.tables.column-toggle.dropdown
                    :form="$this->getTableColumnToggleForm()"
                    :trigger-action="$this->getToggleColumnsTriggerAction()"
                />
            @endif

            <div class="inline-flex items-center min-w-0 md:min-w-38 justify-end">
                {{ $this->applyFiltersAction }}
            </div>
        </div>
    </x-filament::section>

    <x-company.tables.container :report-loaded="$this->reportLoaded">
        @if($this->report)
            <x-company.tables.reports.detailed-report :report="$this->report"/>
        @endif
    </x-company.tables.container>
</x-filament-panels::page>
