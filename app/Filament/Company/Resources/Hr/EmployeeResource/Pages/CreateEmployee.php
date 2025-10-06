<?php

namespace App\Filament\Company\Resources\Hr\EmployeeResource\Pages;

use Filament\Actions;
use App\Models\Hr\Employee;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Company\Resources\Hr\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return Employee::createWithRelations($data);
    }
}
