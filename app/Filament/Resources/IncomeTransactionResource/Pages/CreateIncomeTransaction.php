<?php

namespace App\Filament\Resources\IncomeTransactionResource\Pages;

use App\Filament\Resources\IncomeTransactionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncomeTransaction extends CreateRecord
{
    protected static string $resource = IncomeTransactionResource::class;
}
