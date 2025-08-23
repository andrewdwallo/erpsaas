<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;

class EnhancedStatsOverviewWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;
}
