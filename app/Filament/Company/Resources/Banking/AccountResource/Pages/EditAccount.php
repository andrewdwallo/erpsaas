<?php

namespace App\Filament\Company\Resources\Banking\AccountResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Banking\AccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        return $data;
    }
}
