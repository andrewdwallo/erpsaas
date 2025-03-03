<?php

namespace App\Filament\Company\Pages\Service;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;

class ConnectedAccount extends Page
{
    protected static ?string $title = 'Connected Accounts';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Connect account')
                ->dispatch('createToken'),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenLarge;
    }
}
