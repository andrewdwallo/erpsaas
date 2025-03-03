<?php

namespace App\Filament\Company\Pages\Concerns;

use App\Support\Column;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Arr;

trait HasTableColumnToggleForm
{
    public array $toggledTableColumns = [];

    public function mountHasTableColumnToggleForm(): void
    {
        if (! count($this->toggledTableColumns ?? [])) {
            $this->getTableColumnToggleForm()->fill(session()->get(
                $this->getTableColumnToggleFormStateSessionKey(),
                $this->getDefaultTableColumnToggleState()
            ));
        }
    }

    protected function getDefaultTableColumnToggleState(): array
    {
        $state = [];

        foreach ($this->getTable() as $column) {
            if (! $column->isToggleable()) {
                continue;
            }

            data_set($state, $column->getName(), ! $column->isToggledHiddenByDefault());
        }

        return $state;
    }

    public function updatedToggledTableColumns(): void
    {
        session()->put([
            $this->getTableColumnToggleFormStateSessionKey() => $this->toggledTableColumns,
        ]);
    }

    public function getTableColumnToggleForm(): Form
    {
        if ((! $this->isCachingForms) && $this->hasCachedForm('toggleTableColumnForm')) {
            return $this->getForm('toggleTableColumnForm');
        }

        return $this->makeForm()
            ->schema($this->getTableColumnToggleFormSchema())
            ->statePath('toggledTableColumns')
            ->live();
    }

    protected function hasToggleableColumns(): bool
    {
        return ! empty($this->getTableColumnToggleFormSchema());
    }

    /**
     * @return array<Checkbox>
     */
    protected function getTableColumnToggleFormSchema(): array
    {
        $schema = [];

        foreach ($this->getTable() as $column) {
            if (! $column->isToggleable()) {
                continue;
            }

            $schema[] = Checkbox::make($column->getName())
                ->label($column->getLabel());
        }

        return $schema;
    }

    public function isTableColumnToggledHidden(string $name): bool
    {
        return Arr::has($this->toggledTableColumns, $name) && ! data_get($this->toggledTableColumns, $name);
    }

    public function getTableColumnToggleFormStateSessionKey(): string
    {
        $table = class_basename($this::class);

        return "tables.{$table}_toggled_columns";
    }

    public function getToggleColumnsTriggerAction(): Action
    {
        return Action::make('toggleColumns')
            ->label(__('filament-tables::table.actions.toggle_columns.label'))
            ->iconButton()
            ->size(ActionSize::Large)
            ->icon(FilamentIcon::resolve('tables::actions.toggle-columns') ?? 'heroicon-m-view-columns')
            ->color('gray')
            ->livewireClickHandlerEnabled(false);
    }

    protected function getToggledColumns(): array
    {
        return array_values(
            array_filter(
                $this->getTable(),
                fn (Column $column) => ! $column->isToggleable() || ($this->toggledTableColumns[$column->getName()] ?? false)
            )
        );
    }
}
