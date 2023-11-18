<?php

namespace App\Providers;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Navigation\NavigationGroup;
use Filament\Resources\Components\Tab;
use Filament\Tables\Columns\Column;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Field::macro('localizeLabel', function (string | Htmlable | Closure | null $customLabel = null): static {
            if (filled($customLabel)) {
                $label = $customLabel;
            } else {
                $label = $this->getLabel();

                if (str_ends_with($label, ' id')) {
                    $label = str_replace(' id', '', $label);
                }

                $label = ucwords($label);
            }

            $this->label(translate($label));

            return $this;
        });

        Column::macro('localizeLabel', function (string | Htmlable | Closure | null $customLabel = null): static {
            if (filled($customLabel)) {
                $label = $customLabel;
            } else {
                $label = $this->getLabel();

                if (str_ends_with($label, ' id')) {
                    $label = str_replace(' id', '', $label);
                }

                $label = ucwords($label);
            }

            $this->label(translate($label));

            return $this;
        });

        NavigationGroup::macro('localizeLabel', function () {
            $label = $this->getLabel();

            if (filled($label)) {
                $this->label(translate($label));
            }

            return $this;
        });

        Tab::macro('localizeLabel', function () {
            $label = $this->getLabel();

            $this->label(ucfirst(translate($label)));

            return $this;
        });
    }
}
