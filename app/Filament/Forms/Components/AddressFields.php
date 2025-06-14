<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

class AddressFields extends Grid
{
    protected bool $isSoftRequired = false;

    protected bool | Closure $isCountryDisabled = false;

    protected bool | Closure $isRequired = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([
            TextInput::make('address_line_1')
                ->label('Address line 1')
                ->required(fn () => $this->isRequired())
                ->maxLength(255),
            TextInput::make('address_line_2')
                ->label('Address line 2')
                ->maxLength(255),
            CountrySelect::make('country_code')
                ->disabled(fn () => $this->isCountryDisabled())
                ->clearStateField()
                ->required(fn () => $this->isRequired()),
            StateSelect::make('state_id'),
            TextInput::make('city')
                ->label('City')
                ->required(fn () => $this->isRequired())
                ->maxLength(255),
            TextInput::make('postal_code')
                ->label('Postal code')
                ->maxLength(255),
        ]);
    }

    public function softRequired(bool $condition = true): static
    {
        $this->isSoftRequired = $condition;

        // Defer the soft required logic to after component initialization
        $this->afterStateHydrated(function () use ($condition) {
            $this->applySoftRequired($condition);
        });

        return $this;
    }

    protected function applySoftRequired(bool $condition): void
    {
        if (! $this->hasContainer()) {
            return;
        }

        $childComponents = $this->getChildComponents();

        foreach ($childComponents as $component) {
            if ($component instanceof Field && $component->isRequired()) {
                $component->markAsRequired(! $condition);
            }
        }
    }

    public function required(bool | Closure $condition = true): static
    {
        $this->isRequired = $condition;

        return $this;
    }

    public function isRequired(): bool
    {
        return (bool) $this->evaluate($this->isRequired);
    }

    public function disabledCountry(bool | Closure $condition = true): static
    {
        $this->isCountryDisabled = $condition;

        return $this;
    }

    public function isCountryDisabled(): bool
    {
        return $this->evaluate($this->isCountryDisabled);
    }

    protected function hasContainer(): bool
    {
        try {
            return isset($this->container);
        } catch (\Error $e) {
            return false;
        }
    }
}
