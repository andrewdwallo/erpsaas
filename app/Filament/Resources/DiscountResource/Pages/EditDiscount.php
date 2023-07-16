<?php

namespace App\Filament\Resources\DiscountResource\Pages;

use App\Filament\Resources\DiscountResource;
use App\Traits\HandlesResourceRecordUpdate;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDiscount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = DiscountResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->handleRecordUpdateWithUniqueField($record, $data, 'type');
    }
}
