<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Filament::serving(static function () {
            Filament::registerViteTheme('resources/css/filament.css');
        });

        Field::macro('tooltip', function (string $tooltip) {
            return $this->label(
                Action::make('info')
                    ->label('')
                    ->icon('heroicon-o-information-circle')
                    ->extraAttributes(['class' => 'text-gray-500'])
                    ->tooltip($tooltip),
            );
        });
    }
}
