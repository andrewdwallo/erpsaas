<?php

namespace App\Providers;

use App\Actions\FilamentCompanies\AddCompanyEmployee;
use App\Actions\FilamentCompanies\CreateCompany;
use App\Actions\FilamentCompanies\CreateConnectedAccount;
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
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Wallo\FilamentCompanies\Actions\GenerateRedirectForProvider;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\User\APITokens;
use Wallo\FilamentCompanies\Pages\User\Profile;
use Wallo\FilamentCompanies\Socialite;

class FilamentCompaniesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (FilamentCompanies::hasCompanyFeatures()) {
            Filament::registerRenderHook(
                'global-search.end',
                static fn (): string => Blade::render('<x-filament-companies::dropdown.navigation-menu />'),
            );
        }

        Filament::serving(static function () {
            Filament::registerUserMenuItems([
                'account' => UserMenuItem::make()->url(Profile::getUrl()),
            ]);
        });

        if (FilamentCompanies::hasApiFeatures()) {
            Filament::serving(static function () {
                Filament::registerUserMenuItems([
                    UserMenuItem::make()
                        ->label('API Tokens')
                        ->icon('heroicon-s-lock-open')
                        ->url(APITokens::getUrl()),
                ]);
            });
        }

        Filament::serving(static function () {
            Filament::registerUserMenuItems([
                'logout' => UserMenuItem::make()->url(route('logout')),
            ]);
        });

        RedirectResponse::macro('banner', function ($message) {
            return $this->with('flash', [
                'bannerStyle' => 'success',
                'banner' => $message,
            ]);
        });

        RedirectResponse::macro('dangerBanner', function ($message) {
            return $this->with('flash', [
                'bannerStyle' => 'danger',
                'banner' => $message,
            ]);
        });

        Filament::registerRenderHook(
            'content.start',
            static fn (): string => Blade::render('<x-filament-companies::banner />'),
        );

        $this->configurePermissions();

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
