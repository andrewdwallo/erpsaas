<?php

namespace App\Infolists\Components;

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

    protected string $view = 'infolists.components.report-entry';
}
