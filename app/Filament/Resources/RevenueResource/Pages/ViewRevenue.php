<?php

namespace App\Filament\Resources\RevenueResource\Pages;

use App\Filament\Resources\RevenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRevenue extends ViewRecord
{
    protected static string $resource = RevenueResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
