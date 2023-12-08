<?php

namespace App\Providers;

use App\Actions\FilamentCompanies\AddCompanyEmployee;
use App\Actions\FilamentCompanies\CreateConnectedAccount;
use App\Actions\FilamentCompanies\CreateNewUser;
use App\Actions\FilamentCompanies\CreateUserFromProvider;
use App\Actions\FilamentCompanies\DeleteCompany;
use App\Actions\FilamentCompanies\DeleteUser;
use App\Actions\FilamentCompanies\HandleInvalidState;
use App\Actions\FilamentCompanies\InviteCompanyEmployee;
use App\Actions\FilamentCompanies\RemoveCompanyEmployee;
use App\Actions\FilamentCompanies\ResolveSocialiteUser;
use App\Actions\FilamentCompanies\SetUserPassword;
use App\Actions\FilamentCompanies\UpdateCompanyName;
use App\Actions\FilamentCompanies\UpdateConnectedAccount;
use App\Actions\FilamentCompanies\UpdateUserPassword;
use App\Actions\FilamentCompanies\UpdateUserProfileInformation;
use App\Filament\Company\Pages\CreateCompany;
use App\Http\Middleware\ConfigureCurrentCompany;
use App\Models\Company;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wallo\FilamentCompanies\Actions\GenerateRedirectForProvider;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\Auth\Login;
use Wallo\FilamentCompanies\Pages\Auth\Register;
use Wallo\FilamentCompanies\Pages\Company\CompanySettings;
use Wallo\FilamentCompanies\Providers;
use Wallo\FilamentCompanies\Socialite;

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
                'gray' => Color::Gray,
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
            ->tenantMiddleware([
                ConfigureCurrentCompany::class,
            ], isPersistent: true)
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
        $this->configureDefaults();

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

    /**
     * Configure the default settings for Filament.
     */
    protected function configureDefaults(): void
    {
        $this->configureSelect();

        DatePicker::configureUsing(static function (DatePicker $component) {
            $component->native(false);
        });

        DateTimePicker::configureUsing(static function (DateTimePicker $component) {
            $component->native(false);
        });
    }

    /**
     * Configure the default settings for the Select component.
     */
    protected function configureSelect(): void
    {
        Select::configureUsing(function (Select $select): void {
            $isSelectable = fn (): bool => ! $this->hasRequiredRule($select);

            $select
                ->native(false)
                ->selectablePlaceholder($isSelectable);
        }, isImportant: true);
    }

    protected function hasRequiredRule(Select $component): bool
    {
        $rules = $component->getValidationRules();

        return in_array('required', $rules, true);
    }
}
