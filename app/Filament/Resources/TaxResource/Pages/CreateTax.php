<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use App\Models\Setting\Tax;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTax extends CreateRecord
{
    protected static string $resource = TaxResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['enabled'] = (bool)$data['enabled'];
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $currentCompanyId = auth()->user()->currentCompany->id;
            $type = $data['type'] ?? null;
            $enabled = (bool)($data['enabled'] ?? false);

            if ($enabled === true) {
                $this->disableExistingRecord($currentCompanyId, $type);
            } else {
                $this->ensureAtLeastOneEnabled($currentCompanyId, $type, $enabled);
            }

            $data['enabled'] = $enabled;

            return parent::handleRecordCreation($data);
        });
    }

    protected function disableExistingRecord(int $companyId, string $type): void
    {
        $existingEnabledRecord = Tax::where('company_id', $companyId)
            ->where('enabled', true)
            ->where('type', $type)
            ->first();

        if ($existingEnabledRecord !== null) {
            $existingEnabledRecord->enabled = false;
            $existingEnabledRecord->save();
        }
    }

    protected function ensureAtLeastOneEnabled(int $companyId, string $type, bool &$enabled): void
    {
        $otherEnabledRecords = Tax::where('company_id', $companyId)
            ->where('enabled', true)
            ->where('type', $type)
            ->count();

        if ($otherEnabledRecords === 0) {
            $enabled = true;
        }
    }
}
