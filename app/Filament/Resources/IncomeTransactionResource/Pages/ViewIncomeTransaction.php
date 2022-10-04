<?php

namespace App\Filament\Resources\IncomeTransactionResource\Pages;

use App\Filament\Resources\IncomeTransactionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIncomeTransaction extends ViewRecord
{
    protected static string $resource = IncomeTransactionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
