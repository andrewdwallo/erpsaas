<?php

namespace App\Filament\Resources\LiabilityResource\Pages;

use App\Filament\Resources\LiabilityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiabilities extends ListRecords
{
    protected static string $resource = LiabilityResource::class;

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
