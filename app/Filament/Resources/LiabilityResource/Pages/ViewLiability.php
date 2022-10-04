<?php

namespace App\Filament\Resources\LiabilityResource\Pages;

use App\Filament\Resources\LiabilityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLiability extends ViewRecord
{
    protected static string $resource = LiabilityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
