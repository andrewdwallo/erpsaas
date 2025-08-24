<?php

namespace App\Filament\Widgets\EnhancedStatsOverviewWidget;

use Closure;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\Support\Htmlable;

class EnhancedStat extends Stat
{
    protected string $view = 'filament.widgets.enhanced-stats-overview-widget.enhanced-stat';

    protected string | Htmlable | Closure | null $prefixLabel = null;

    protected string | Htmlable | Closure | null $suffixLabel = null;

    public function prefix(string | Htmlable | Closure | null $label): static
    {
        $this->prefixLabel = $label;

        return $this;
    }

    public function suffix(string | Htmlable | Closure | null $label): static
    {
        $this->suffixLabel = $label;

        return $this;
    }

    public function getPrefixLabel(): string | Htmlable | null
    {
        return $this->evaluate($this->prefixLabel);
    }

    public function getSuffixLabel(): string | Htmlable | null
    {
        return $this->evaluate($this->suffixLabel);
    }
}
