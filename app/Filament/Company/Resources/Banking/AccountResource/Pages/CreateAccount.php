<?php

namespace App\Filament\Company\Resources\Banking\AccountResource\Pages;

use App\Filament\Company\Resources\Banking\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        Log::info('CreateAccount::mutateFormDataBeforeCreate', $data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        Log::info('CreateAccount::handleRecordCreation', $data);

        return parent::handleRecordCreation($data);
    }
}
