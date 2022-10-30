<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Filament::registerScripts([app(Vite::class)('resources/filament/filament-turbo.js')]);
        Filament::registerScripts([app(Vite::class)('resources/filament/filament-stimulus.js')]);
        Filament::serving(function () {
            // Using Vite 
            Filament::registerTheme(
                app(Vite::class)('resources/css/filament.css'),
            );
            Filament::registerNavigationGroups([
                'Resource Management',
                'Account Management',
                'Bank',
            ]);
        });
    }
}
