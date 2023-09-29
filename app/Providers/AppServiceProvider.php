<?php

namespace App\Providers;

use App\Enums\PrimaryColor;
use Closure;
use Filament\Forms\Components\{Select, TextInput};
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\{ServiceProvider, Str};

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
        Notifications::alignment(Alignment::Center);

        TextColumn::macro('currency', function (string | Closure | null $currency = null, ?bool $convert = null): static {
            $this->formatStateUsing(static function (TextColumn $column, $state) use ($currency, $convert): ?string {
                if (blank($state)) {
                    return null;
                }

                $currency = $column->evaluate($currency);
                $convert = $column->evaluate($convert);

                return money($state, $currency, $convert);
            });

            return $this;
        });

        TextInput::macro('currency', function (string | Closure | null $currency = null): static {
            $this->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getPrefix();
                })
                ->suffix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getSuffix();
                })
                ->mask(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);
                    $decimal_mark = currency($currency)->getDecimalMark();
                    $thousands_separator = currency($currency)->getThousandsSeparator();
                    $precision = currency($currency)->getPrecision();

                    $jsCode = "\$money(\$input, '" . $decimal_mark . "', '" . $thousands_separator . "', " . $precision . ');';

                    return RawJs::make($jsCode);
                });

            return $this;
        });

        Select::macro('color', function (string | Closure | null $color = null): static {
            $this->options(
                collect(PrimaryColor::caseValues())
                    ->mapWithKeys(static function ($color) {
                        return [$color => Str::title($color)];
                    })
                    ->toArray()
            )
                ->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (Select $component) use ($color) {
                    $color = $component->evaluate($color);

                    return '<span class="text-' . $color . '-500">●</span>';
                });

            return $this;
        });
    }
}
