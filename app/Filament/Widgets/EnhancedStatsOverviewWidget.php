<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;

class EnhancedStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = false;
}
