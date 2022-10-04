<?php

namespace App\Filament\Resources\EquityResource\Pages;

use App\Filament\Resources\EquityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquity extends EditRecord
{
    protected static string $resource = EquityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
