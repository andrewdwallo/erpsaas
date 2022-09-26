<?php

namespace App\Filament\Resources\BankResource\Pages;

use App\Filament\Resources\BankResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBank extends ViewRecord
{
    protected static string $resource = BankResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
