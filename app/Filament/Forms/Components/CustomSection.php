<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Section;
use Filament\Support\Concerns\CanBeContained;

class CustomSection extends Section
{
    use CanBeContained;

    protected string $view = 'filament.forms.components.custom-section';
}
