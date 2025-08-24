<?php

namespace App\Filament\Company\Clusters\Settings\Resources\Currencies\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Clusters\Settings\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = CurrencyResource::class;
}
