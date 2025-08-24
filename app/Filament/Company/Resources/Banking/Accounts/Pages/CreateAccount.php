<?php

namespace App\Filament\Company\Resources\Banking\Accounts\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Banking\Accounts\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = AccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        return $data;
    }
}
