<?php

namespace App\Filament\Resources\EquityResource\Pages;

use App\Filament\Resources\EquityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEquity extends ViewRecord
{
    protected static string $resource = EquityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
