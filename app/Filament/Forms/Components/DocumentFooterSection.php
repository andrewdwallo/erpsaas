<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;

class DocumentFooterSection extends Section
{
    protected string | Closure | null $defaultFooter = null;

    public function defaultFooter(string | Closure | null $footer): static
    {
        $this->defaultFooter = $footer;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->collapsible();
        $this->collapsed();

        $this->schema([
            Textarea::make('footer')
                ->default(fn () => $this->getDefaultFooter())
                ->columnSpanFull(),
        ]);
    }

    public function getDefaultFooter(): ?string
    {
        return $this->evaluate($this->defaultFooter);
    }
}
