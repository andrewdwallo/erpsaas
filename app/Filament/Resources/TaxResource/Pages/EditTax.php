<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use App\Models\Setting\Tax;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditTax extends EditRecord
{
    protected static string $resource = TaxResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['enabled'] = (bool)$data['enabled'];
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordUpdate(Model|Tax $record, array $data): Model|Tax
    {
        return DB::transaction(function () use ($record, $data) {
            $currentCompanyId = auth()->user()->currentCompany->id;
            $recordId = $record->id;
            $oldType = $record->type;
            $newType = $data['type'];
            $enabled = (bool)($data['enabled'] ?? false);

            // If the record type has changed and it was previously enabled
            if ($oldType !== $newType && $record->enabled) {
                $this->changeRecordType($currentCompanyId, $recordId, $oldType);
            }

            if ($enabled === true) {
                $this->disableExistingRecord($currentCompanyId, $recordId, $newType);
            } elseif ($enabled === false) {
                $this->ensureAtLeastOneEnabled($currentCompanyId, $recordId, $newType, $enabled);
            }

            $data['enabled'] = $enabled;

            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function changeRecordType(int $companyId, int $recordId, string $oldType): void
    {
        $oldTypeRecord = $this->getCompanyCategoryRecord($companyId, $oldType, $recordId);

        if ($oldTypeRecord) {
            $oldTypeRecord->enabled = true;
            $oldTypeRecord->save();
        }
    }

    protected function disableExistingRecord(int $companyId, int $recordId, string $newType): void
    {
        $existingEnabledRecord = $this->getCompanyCategoryRecord($companyId, $newType, $recordId);

        if ($existingEnabledRecord !== null) {
            $existingEnabledRecord->enabled = false;
            $existingEnabledRecord->save();
        }
    }

    protected function ensureAtLeastOneEnabled(int $companyId, int $recordId, string $newType, bool &$enabled): void
    {
        $otherEnabledRecords = Tax::where('company_id', $companyId)
            ->where('enabled', true)
            ->where('type', $newType)
            ->where('id', '!=', $recordId)
            ->count();

        if ($otherEnabledRecords === 0) {
            $enabled = true;
        }
    }

    protected function getCompanyCategoryRecord(int $companyId, string $type, int $recordId): ?Tax
    {
        return Tax::where('company_id', $companyId)
            ->where('type', $type)
            ->where('id', '!=', $recordId)
            ->first();
    }


}
