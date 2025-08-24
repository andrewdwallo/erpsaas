<?php

namespace App\Providers\Filament;

use App\Filament\Components\PanelShiftDropdown;
use App\Filament\User\Clusters\Account\AccountCluster;
use App\Http\Middleware\Authenticate;
use Exception;
use Filament\Facades\Filament;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\User\PersonalAccessTokens;
use Wallo\FilamentCompanies\Pages\User\Profile;

class UserPanelProvider extends PanelProvider
{
    /**
     * @throws Exception
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('user')
            ->path('user')
            ->plugin(
                PanelShiftDropdown::make()
                    ->logoutItem()
                    ->companySettings(false)
                    ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                        return $builder
                            ->items([
                                ...AccountCluster::getNavigationItems(),
                                NavigationItem::make('company')
                                    ->label('Company Dashboard')
                                    ->icon('heroicon-s-building-office-2')
                                    ->url(static function (): ?string {
                                        $user = Auth::user();
                                        $companyPanel = FilamentCompanies::getCompanyPanel();

                                        if ($company = $user?->primaryCompany()) {
                                            return Filament::getPanel($companyPanel)->getUrl(tenant: $company);
                                        }

                                        return Filament::getPanel($companyPanel)->getTenantRegistrationUrl();
                                    }),
                            ]);
                    }),
            )
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigation(false)
            ->viteTheme('resources/css/filament/user/theme.css')
            ->brandLogo(static fn () => view('components.icons.logo'))
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->discoverClusters(in: app_path('Filament/User/Clusters'), for: 'App\\Filament\\User\\Clusters')
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->pages([
                Profile::class,
                PersonalAccessTokens::class,
            ])
            ->widgets([
                //
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
