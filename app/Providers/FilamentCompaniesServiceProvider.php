<?php

namespace App\Providers;

use App\Actions\FilamentCompanies\{AddCompanyEmployee, CreateConnectedAccount, CreateNewUser, CreateUserFromProvider, DeleteCompany, DeleteUser, HandleInvalidState, InviteCompanyEmployee, RemoveCompanyEmployee, ResolveSocialiteUser, SetUserPassword, UpdateCompanyName, UpdateConnectedAccount, UpdateUserPassword, UpdateUserProfileInformation};
use App\Filament\Company\Pages\CreateCompany;
use App\Models\Company;
use Filament\Http\Middleware\{Authenticate, DisableBladeIconComponents, DispatchServingFilamentEvent};
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\{Pages, Panel, PanelProvider, Widgets};
use Illuminate\Cookie\Middleware\{AddQueuedCookiesToResponse, EncryptCookies};
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\{AuthenticateSession, StartSession};
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wallo\FilamentCompanies\Actions\GenerateRedirectForProvider;
use Wallo\FilamentCompanies\Pages\Auth\{Login, Register};
use Wallo\FilamentCompanies\Pages\Company\CompanySettings;
use Wallo\FilamentCompanies\Pages\User\Profile;
use Wallo\FilamentCompanies\{FilamentCompanies, Providers, Socialite};

class FilamentCompaniesServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('company')
            ->path('company')
            ->login(Login::class)
            ->registration(Register::class)
            ->passwordReset()
            ->homeUrl(static fn (): string => url(Pages\Dashboard::getUrl(panel: 'company', tenant: Auth::user()?->personalCompany())))
            ->plugin(
                FilamentCompanies::make()
                    ->userPanel('user')
                    ->switchCurrentCompany()
                    ->updateProfileInformation()
                    ->updatePasswords()
                    ->setPasswords()
                    ->connectedAccounts()
                    ->manageBrowserSessions()
                    ->accountDeletion()
                    ->profilePhotos()
                    ->api()
                    ->companies(invitations: true)
                    ->termsAndPrivacyPolicy()
                    ->notifications()
                    ->modals()
                    ->socialite(
                        providers: [Providers::github()],
                        features: [Socialite::rememberSession(), Socialite::providerAvatars()]
                    ),
            )
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->viteTheme('resources/css/filament/company/theme.css')
            ->tenant(Company::class)
            ->tenantProfile(CompanySettings::class)
            ->tenantRegistration(CreateCompany::class)
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url(static fn () => route(Profile::getRouteName(panel: 'user'))),
            ])
            ->authGuard('web')
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        FilamentCompanies::createUsersUsing(CreateNewUser::class);
        FilamentCompanies::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        FilamentCompanies::updateUserPasswordsUsing(UpdateUserPassword::class);

        FilamentCompanies::createCompaniesUsing(CreateCompany::class);
        FilamentCompanies::updateCompanyNamesUsing(UpdateCompanyName::class);
        FilamentCompanies::addCompanyEmployeesUsing(AddCompanyEmployee::class);
        FilamentCompanies::inviteCompanyEmployeesUsing(InviteCompanyEmployee::class);
        FilamentCompanies::removeCompanyEmployeesUsing(RemoveCompanyEmployee::class);
        FilamentCompanies::deleteCompaniesUsing(DeleteCompany::class);
        FilamentCompanies::deleteUsersUsing(DeleteUser::class);

        Socialite::resolvesSocialiteUsersUsing(ResolveSocialiteUser::class);
        Socialite::createUsersFromProviderUsing(CreateUserFromProvider::class);
        Socialite::createConnectedAccountsUsing(CreateConnectedAccount::class);
        Socialite::updateConnectedAccountsUsing(UpdateConnectedAccount::class);
        Socialite::setUserPasswordsUsing(SetUserPassword::class);
        Socialite::handlesInvalidStateUsing(HandleInvalidState::class);
        Socialite::generatesProvidersRedirectsUsing(GenerateRedirectForProvider::class);
    }

    /**
     * Configure the roles and permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        FilamentCompanies::defaultApiTokenPermissions(['read']);

        FilamentCompanies::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        FilamentCompanies::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users have the ability to read, create, and update.');
    }
}
