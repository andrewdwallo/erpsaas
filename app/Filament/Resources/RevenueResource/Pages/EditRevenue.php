<?php

namespace App\Filament\Resources\RevenueResource\Pages;

use App\Filament\Resources\RevenueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevenue extends EditRecord
{
    protected static string $resource = RevenueResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
