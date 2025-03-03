<?php

namespace App\Filament\Company\Pages\Concerns;

use Livewire\Attributes\Url;

trait HasReportTabs
{
    #[Url]
    public ?string $activeTab = 'summary';

    public function getTabs(): array
    {
        return [
            'summary' => 'Summary',
            'details' => 'Details',
        ];
    }

    public function getActiveTab(): string
    {
        return $this->activeTab;
    }
}
