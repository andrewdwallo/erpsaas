<?php

namespace App\Filament\Company\Pages\Service;

use App\Services\PlaidService;
use Filament\Actions\Action;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ConnectedAccount extends Page
{
    protected static ?string $title = 'Connected Accounts';

    protected static ?string $slug = 'services/connected-accounts';

    protected string $view = 'filament.company.pages.service.connected-account';

    public static function canAccess(): bool
    {
        return app(PlaidService::class)->isEnabled();
    }

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

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->visible(static::canAccess())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getNavigationUrl()),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::ScreenLarge;
    }
}
