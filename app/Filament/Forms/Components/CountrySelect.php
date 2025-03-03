<?php

namespace App\Filament\Forms\Components;

use App\Models\Locale\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;

class CountrySelect extends Select
{
    protected ?string $stateFieldName = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->localizeLabel('Country')
            ->searchable()
            ->options($options = Country::getAvailableCountryOptions())
            ->getSearchResultsUsing(static fn (string $search): array => Country::getSearchResultsUsing($search))
            ->getOptionLabelUsing(static fn (string $value): ?string => $options[$value] ?? $value);

        $this->afterStateUpdated(function (self $component, Set $set) {
            if ($component->shouldClearStateField()) {
                $set($component->getStateFieldName(), null);
            }
        });
    }

    public function clearStateField(string $fieldName = 'state_id'): static
    {
        $this->stateFieldName = $fieldName;

        $this->live();

        return $this;
    }

    public function getStateFieldName(): ?string
    {
        return $this->stateFieldName;
    }

    public function shouldClearStateField(): bool
    {
        return (bool) $this->stateFieldName;
    }
}
