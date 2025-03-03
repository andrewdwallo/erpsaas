<?php

namespace App\Filament\Company\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Form;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

trait HasFiltersForm
{
    /**
     * @var array<string, mixed> | null
     */
    #[Url(keep: true)]
    public ?array $filters = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $deferredFilters = null;

    public function mountHasFiltersForm(): void
    {
        if (method_exists($this, 'loadDefaultDateRange')) {
            $this->loadDefaultDateRange();
        }

        $this->initializeFilters();
    }

    public function initializeFilters(): void
    {
        if (! count($this->filters ?? [])) {
            $this->filters = null;
        }

        $this->getFiltersForm()->fill($this->filters ?? []);

        $this->applyFilters();
    }

    protected function getForms(): array
    {
        return [
            'toggledTableColumnForm',
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

        $this->handleFilterUpdates();
    }

    protected function isValidDate($date): bool
    {
        return strtotime($date) !== false;
    }

    protected function handleFilterUpdates(): void
    {
        //
    }

    public function applyFilters(): void
    {
        $normalizedFilters = $this->deferredFilters;

        $this->normalizeFilters($normalizedFilters);

        $this->filters = $normalizedFilters;

        $this->handleFilterUpdates();

        if (method_exists($this, 'loadReportData')) {
            $this->loadReportData();
        }
    }

    protected function normalizeFilters(array &$filters): void
    {
        foreach ($filters as $name => &$value) {
            if ($name === 'dateRange') {
                unset($filters[$name]);
            } elseif ($this->isValidDate($value)) {
                $filters[$name] = Carbon::parse($value)->toDateString();
            }
        }
    }

    public function getFiltersApplyAction(): Action
    {
        return Action::make('applyFilters')
            ->label(__('filament-tables::table.filters.actions.apply.label'))
            ->action('applyFilters')
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
}
