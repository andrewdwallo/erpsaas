<?php

namespace App\Filament\Company\Resources\Accounting\TransactionResource\Pages;

use App\Filament\Company\Resources\Accounting\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
