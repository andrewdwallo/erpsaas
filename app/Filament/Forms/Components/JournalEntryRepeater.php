<?php

namespace App\Filament\Forms\Components;

use Awcodes\TableRepeater\Components\TableRepeater;
use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class JournalEntryRepeater extends TableRepeater
{
    protected View | Htmlable | Closure | null $footerItem = null;

    public function footerItem(View | Htmlable | Closure | null $footer = null): static
    {
        $this->footerItem = $footer;

        return $this;
    }

    public function getFooterItem(): View | Htmlable | null
    {
        return $this->evaluate($this->footerItem);
    }

    public function hasFooterItem(): bool
    {
        return $this->footerItem !== null;
    }

    public function getView(): string
    {
        return 'filament.forms.components.journal-entry-repeater';
    }
}
