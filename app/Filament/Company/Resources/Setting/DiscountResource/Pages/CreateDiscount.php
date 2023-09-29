<?php

namespace App\Filament\Company\Resources\Setting\DiscountResource\Pages;

use App\Filament\Company\Resources\Setting\DiscountResource;
use App\Models\Setting\Discount;
use App\Traits\HandlesResourceRecordCreation;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreateDiscount extends CreateRecord
{
    use HandlesResourceRecordCreation;

    protected static string $resource = DiscountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enabled'] = (bool) $data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        if (! $user) {
            throw new Halt('No authenticated user found');
        }

        return $this->handleRecordCreationWithUniqueField($data, new Discount(), $user, 'type');
    }
}
