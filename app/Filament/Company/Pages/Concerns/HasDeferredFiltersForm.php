<?php

namespace App\Filament\Company\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

trait HasDeferredFiltersForm
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $filters = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $deferredFilters = null;

    public function mountHasDeferredFiltersForm(): void
    {
        $this->initializeDefaultFilters();

        $this->initializeFilters();
    }

    protected function initializeDefaultFilters(): void
    {
        //
    }

    public function initializeFilters(): void
    {
        if (! count($this->filters ?? [])) {
            $this->filters = null;
        }

        $this->getFiltersForm()->fill($this->filters);
    }

    protected function getHasDeferredFiltersFormForms(): array
    {
        return [
            'filtersForm' => $this->getFiltersForm(),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form;
    }

    public function getFiltersForm(): Form
    {
        return $this->filtersForm($this->makeForm()
            ->statePath('deferredFilters'));
    }

    public function updatedFilters(): void
    {
        $this->deferredFilters = $this->filters;
    }

    protected function isValidDate($date): bool
    {
        return strtotime($date) !== false;
    }

    public function applyFilters(): void
    {
        $this->filters = $this->deferredFilters;

        $this->loadReportData();
    }

    public function applyFiltersAction(): Action
    {
        return Action::make('applyFilters')
            ->label('Update report')
            ->action('applyFilters')
            ->keyBindings(['mod+s'])
            ->button();
    }

    public function getFilterState(string $name): mixed
    {
        return Arr::get($this->filters, $name);
    }

    public function setFilterState(string $name, mixed $value): void
    {
        Arr::set($this->filters, $name, $value);
    }

    public function getDeferredFilterState(string $name): mixed
    {
        return Arr::get($this->deferredFilters, $name);
    }

    public function setDeferredFilterState(string $name, mixed $value): void
    {
        Arr::set($this->deferredFilters, $name, $value);
    }

    protected function convertDatesToDateTimeString(array $filters): array
    {
        if (isset($filters['startDate'])) {
            $filters['startDate'] = Carbon::parse($filters['startDate'])->startOfDay()->toDateTimeString();
        }

        if (isset($filters['endDate'])) {
            $filters['endDate'] = Carbon::parse($filters['endDate'])->endOfDay()->toDateTimeString();
        }

        return $filters;
    }

    protected function queryStringHasDeferredFiltersForm(): array
    {
        // Get the filter keys dynamically from the filters form
        $filterKeys = collect($this->getFiltersForm()->getFlatFields())->keys()->toArray();

        return array_merge(
            $this->generateQueryStrings($filterKeys),
            $this->generateExcludedQueryStrings(),
        );
    }

    protected function generateQueryStrings(array $filterKeys): array
    {
        $generatedQueryStrings = [];

        $excludedKeys = $this->excludeQueryStrings();

        foreach ($filterKeys as $key) {
            if (! in_array($key, $excludedKeys)) {
                $generatedQueryStrings["filters.{$key}"] = [
                    'as' => $key,
                    'keep' => true,
                ];
            }
        }

        return $generatedQueryStrings;
    }

    protected function generateExcludedQueryStrings(): array
    {
        $excludedQueryStrings = [];

        foreach ($this->excludeQueryStrings() as $key) {
            $excludedQueryStrings["filters.{$key}.value"] = null;
        }

        return $excludedQueryStrings;
    }

    protected function excludeQueryStrings(): array
    {
        return [
            'dateRange',
        ];
    }

    public function dehydrateHasDeferredFiltersForm(): void
    {
        $flatFields = $this->getFiltersForm()->getFlatFields();

        foreach ($this->filters as $key => $value) {
            if (isset($flatFields[$key]) && $flatFields[$key] instanceof DatePicker) {
                // TODO: Submit a PR to Filament to address DatePicker being dehydrated as a datetime string in filters
                $this->filters[$key] = Carbon::parse($value)->toDateString();
            }
        }
    }
}
