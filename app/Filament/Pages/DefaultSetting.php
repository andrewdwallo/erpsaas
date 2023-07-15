<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DefaultSetting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationLabel = 'Default';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Default';

    protected static ?string $slug = 'default';

    protected static string $view = 'filament.pages.default-setting';
}
