<?php

namespace App\Filament\Forms\Components;

use App\Models\Locale\State;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;

class StateSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->localizeLabel('State / Province')
            ->searchable()
            ->options(static fn (Get $get) => State::getStateOptions($get('country_code')))
            ->getSearchResultsUsing(static function (string $search, Get $get): array {
                return State::getSearchResultsUsing($search, $get('country_code'));
            });
    }
}
