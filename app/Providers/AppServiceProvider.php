<?php

namespace App\Providers;

use App\Http\Responses\LoginRedirectResponse;
use App\Models\Export;
use App\Models\Import;
use App\Models\Notification;
use App\Services\DateRangeService;
use Filament\Actions\Exports\Models\Export as BaseExport;
use Filament\Actions\Imports\Models\Import as BaseImport;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Notifications\Livewire\Notifications;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Notifications\DatabaseNotification;
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
        // Bind custom Import and Export models
        $this->app->bind(BaseImport::class, Import::class);
        $this->app->bind(BaseExport::class, Export::class);

        // Bind custom Notification model
        $this->app->bind(DatabaseNotification::class, Notification::class);

        Notifications::alignment(Alignment::Center);

        FilamentAsset::register([
            Js::make('top-navigation', __DIR__ . '/../../resources/js/top-navigation.js'),
            Js::make('history-fix', __DIR__ . '/../../resources/js/history-fix.js'),
            Js::make('custom-print', __DIR__ . '/../../resources/js/custom-print.js'),
        ]);

        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset
            ->columnSpanFull());

        Grid::configureUsing(fn (Grid $grid) => $grid
            ->columnSpanFull());

        Section::configureUsing(fn (Section $section) => $section
            ->columnSpanFull());
    }
}
