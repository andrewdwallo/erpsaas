<?php

namespace App\Filament\Company\Resources\Setting\DiscountResource\Pages;

use App\Enums\DiscountType;
use App\Filament\Company\Resources\Setting\DiscountResource;
use App\Traits\HandlesResourceRecordUpdate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditDiscount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool) $data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        if (! $user) {
            throw new Halt('No authenticated user found');
        }

        $evaluatedTypes = [DiscountType::Sales, DiscountType::Purchase];

        return $this->handleRecordUpdateWithUniqueField($record, $data, $user, 'type', $evaluatedTypes);
    }
}
