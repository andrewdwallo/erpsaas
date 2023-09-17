<?php

namespace App\Filament\Company\Resources\Setting\TaxResource\Pages;

use App\Filament\Company\Resources\Setting\TaxResource;
use App\Models\Setting\Tax;
use App\Traits\HandlesResourceRecordCreation;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateTax extends CreateRecord
{
    use HandlesResourceRecordCreation;

    protected static string $resource = TaxResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enabled'] = (bool)$data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (!$user) {
            throw new Halt('No authenticated user found');
        }

        return $this->handleRecordCreationWithUniqueField($data, new Tax(), $user, 'type');
    }
}
