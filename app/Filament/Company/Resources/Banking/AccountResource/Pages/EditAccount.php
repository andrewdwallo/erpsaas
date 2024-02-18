<?php

namespace App\Filament\Company\Resources\Banking\AccountResource\Pages;

use App\Filament\Company\Resources\Banking\AccountResource;
use App\Traits\HandlesResourceRecordUpdate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        return $data;
    }
}
