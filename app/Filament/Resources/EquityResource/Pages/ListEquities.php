<?php

namespace App\Filament\Resources\EquityResource\Pages;

use App\Filament\Resources\EquityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquities extends ListRecords
{
    protected static string $resource = EquityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
