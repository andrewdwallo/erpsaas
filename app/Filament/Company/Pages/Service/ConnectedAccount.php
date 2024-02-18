<?php

namespace App\Filament\Company\Pages\Service;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class ConnectedAccount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $title = 'Connected Accounts';

    protected static ?string $navigationGroup = 'Services';

    protected static ?string $slug = 'services/connected-accounts';

    protected static string $view = 'filament.company.pages.service.connected-account';

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return translate('Banking');
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Connect Account')
                ->dispatch('createToken'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenLarge;
    }
}
