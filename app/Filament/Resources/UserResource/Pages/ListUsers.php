<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
