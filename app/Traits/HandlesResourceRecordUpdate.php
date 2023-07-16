<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HandlesResourceRecordUpdate
{
    protected function handleRecordUpdateWithUniqueField(Model $record, array $data, string|null $uniqueField = null): Model
    {
        return DB::transaction(function () use ($uniqueField, $record, $data) {
            $companyId = Auth::user()->currentCompany->id;
            $oldValue = $uniqueField ? $record->{$uniqueField} : null;
            $newValue = $uniqueField ? $data[$uniqueField] : null;
            $enabled = (bool)($data['enabled'] ?? false);

            if ($oldValue !== $newValue && $record->enabled) {
                $this->enableAnotherOfSameValue($companyId, $record, $uniqueField, $oldValue);
            }

            if ($enabled === true) {
                $this->disableOthersOfSameValue($companyId, $record, $uniqueField, $newValue);
            } elseif ($enabled === false) {
                $this->ensureAtLeastOneEnabled($companyId, $record, $uniqueField, $newValue, $enabled);
            }

            $data['enabled'] = $enabled;

            return tap($record)->update($data);
        });
    }

    protected function enableAnotherOfSameValue(int $companyId, Model $record, ?string $uniqueField, $value): void
    {
        $query = $record::where('company_id', $companyId)
            ->where('id', '!=', $record->id)
            ->where('enabled', false);

        if($uniqueField){
            $query->where($uniqueField, $value);
        }

        $otherRecord = $query->first();

        if ($otherRecord) {
            $otherRecord->enabled = true;
            $otherRecord->save();
        }
    }

    protected function disableOthersOfSameValue(int $companyId, Model $record, ?string $uniqueField, $value): void
    {
        $query = $record::where('company_id', $companyId)
            ->where('id', '!=', $record->id)
            ->where('enabled', true);

        if($uniqueField){
            $query->where($uniqueField, $value);
        }

        $query->update(['enabled' => false]);
    }

    protected function ensureAtLeastOneEnabled(int $companyId, Model $record, ?string $uniqueField, $value, bool &$enabled): void
    {
        $query = $record::where('company_id', $companyId)
            ->where('id', '!=', $record->id)
            ->where('enabled', true);

        if($uniqueField){
            $query->where($uniqueField, $value);
        }

        $enabledCount = $query->count();

        if ($enabledCount === 0) {
            $enabled = true;
        }
    }
}
