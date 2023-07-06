<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DefaultSetting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments';

    protected static ?string $navigationLabel = 'Defaults';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Defaults';

    protected static ?string $slug = 'defaults';

    protected static string $view = 'filament.pages.default-setting';

    public function getViewData(): array
    {
        return [
            'defaultSetting' => \App\Models\Setting\DefaultSetting::first(),
        ];
    }


}
