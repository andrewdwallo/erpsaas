<?php

namespace App\Filament\Resources\LiabilityResource\Pages;

use App\Filament\Resources\LiabilityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiability extends EditRecord
{
    protected static string $resource = LiabilityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
