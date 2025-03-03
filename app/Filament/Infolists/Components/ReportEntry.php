<?php

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Filament\Support\Concerns\HasDescription;
use Filament\Support\Concerns\HasHeading;
use Filament\Support\Concerns\HasIcon;
use Filament\Support\Concerns\HasIconColor;

class ReportEntry extends Entry
{
    use HasDescription;
    use HasHeading;
    use HasIcon;
    use HasIconColor;

    protected string $view = 'filament.infolists.components.report-entry';
}
