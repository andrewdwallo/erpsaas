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
            return TranslationServiceProvider::localizeLabelGeneric($this, $customLabel);
        });

        Column::macro('localizeLabel', function (string | Htmlable | Closure | null $customLabel = null): static {
            return TranslationServiceProvider::localizeLabelGeneric($this, $customLabel);
        });

        NavigationGroup::macro('localizeLabel', function () {
            $label = $this->getLabel();

            if (filled($label)) {
                $translatedLabel = translate($label);
                $this->label(ucfirst($translatedLabel));
            }

            return $this;
        });

        Tab::macro('localizeLabel', function () {
            $label = $this->getLabel();

            if (filled($label)) {
                $translatedLabel = translate($label);
                $this->label(ucfirst($translatedLabel));
            }

            return $this;
        });
    }

    public static function localizeLabelGeneric($object, string | Htmlable | Closure | null $customLabel = null)
    {
        $label = filled($customLabel) ? $customLabel : static::processedLabel($object->getLabel());

        $object->label(translate($label));

        return $object;
    }

    public static function processedLabel(Htmlable | null | string $label): string
    {
        if (str_ends_with($label, ' id')) {
            $label = str_replace(' id', '', $label);
        }

        return ucfirst($label);
    }
}
