<?php

namespace App\Concerns;

trait HasTabSpecificColumnToggles
{
    public function getTableColumnToggleFormStateSessionKey(): string
    {
        $table = class_basename($this::class);
        $tab = $this->activeTab;

        return "tables.{$table}_{$tab}_toggled_columns";
    }

    public function updatedActiveTab(): void
    {
        parent::updatedActiveTab();

        // Load saved state for new tab or fall back to defaults
        $this->toggledTableColumns = session(
            $this->getTableColumnToggleFormStateSessionKey(),
            $this->getDefaultTableColumnToggleState()
        );

        $this->updatedToggledTableColumns();
    }
}
