<?php

namespace App\Providers;

use App\Http\Responses\LoginRedirectResponse;
use App\Services\DateRangeService;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DateRangeService::class);
        $this->app->singleton(LoginResponse::class, LoginRedirectResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notifications::alignment(Alignment::Center);

        FilamentAsset::register([
            Js::make('top-navigation', __DIR__ . '/../../resources/js/top-navigation.js'),
            Js::make('history-fix', __DIR__ . '/../../resources/js/history-fix.js'),
        ]);
    }
}
