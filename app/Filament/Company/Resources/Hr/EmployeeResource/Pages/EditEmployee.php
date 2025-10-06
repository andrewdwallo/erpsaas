<?php

namespace App\Filament\Company\Resources\Hr\EmployeeResource\Pages;

use Filament\Actions;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Company\Resources\Hr\EmployeeResource;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Employee $record */
        $record->updateWithRelations($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
