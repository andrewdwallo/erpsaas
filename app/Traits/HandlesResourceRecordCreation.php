<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HandlesResourceRecordCreation
{
    protected function handleRecordCreationWithUniqueField(array $data, Model $model, string|null $uniqueField = null): Model
    {
        return DB::transaction(function () use ($data, $uniqueField, $model) {
            $currentCompanyId = Auth::user()->currentCompany->id;
            $uniqueFieldValue = $data[$uniqueField] ?? null;
            $enabled = (bool)($data['enabled'] ?? false);

            if ($enabled === true) {
                $this->disableExistingRecord($currentCompanyId, $model, $uniqueField, $uniqueFieldValue);
            } else {
                $this->ensureAtLeastOneEnabled($currentCompanyId, $model, $enabled, $uniqueField, $uniqueFieldValue);
            }

            $data['enabled'] = $enabled;

            return $model::create($data);
        });
    }

    protected function disableExistingRecord(int $companyId, Model $model, string|null $uniqueField = null, string|null $uniqueFieldValue = null): void
    {
        $query = $model::where('company_id', $companyId)
            ->where('enabled', true);

        if($uniqueField && $uniqueFieldValue){
            $query->where($uniqueField, $uniqueFieldValue);
        }

        $existingEnabledRecord = $query->first();

        if ($existingEnabledRecord !== null) {
            $existingEnabledRecord->enabled = false;
            $existingEnabledRecord->save();
        }
    }

    protected function ensureAtLeastOneEnabled(int $companyId, Model $model, bool &$enabled, string|null $uniqueField = null, string|null $uniqueFieldValue = null): void
    {
        $query = $model::where('company_id', $companyId)
            ->where('enabled', true);

        if($uniqueField && $uniqueFieldValue){
            $query->where($uniqueField, $uniqueFieldValue);
        }

        $otherEnabledRecords = $query->count();

        if ($otherEnabledRecords === 0) {
            $enabled = true;
        }
    }
}
