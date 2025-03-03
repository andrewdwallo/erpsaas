<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;

class AddressFields extends Grid
{
    protected bool $isSoftRequired = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([
            TextInput::make('address_line_1')
                ->label('Address line 1')
                ->required()
                ->maxLength(255),
            TextInput::make('address_line_2')
                ->label('Address line 2')
                ->maxLength(255),
            CountrySelect::make('country_code')
                ->clearStateField()
                ->required(),
            StateSelect::make('state_id'),
            TextInput::make('city')
                ->label('City')
                ->required()
                ->maxLength(255),
            TextInput::make('postal_code')
                ->label('Postal code')
                ->maxLength(255),
        ]);
    }

    public function softRequired(bool $condition = true): static
    {
        $this->setSoftRequired($condition);

        return $this;
    }

    protected function setSoftRequired(bool $condition): void
    {
        $this->isSoftRequired = $condition;

        $childComponents = $this->getChildComponents();

        foreach ($childComponents as $component) {
            if ($component instanceof Field && $component->isRequired()) {
                $component->markAsRequired(! $condition);
            }
        }
    }
}
