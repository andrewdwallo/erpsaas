<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\{Builder, Model};

trait HandlesResourceRecordCreation
{
    protected function handleRecordCreationWithUniqueField(array $data, Model $model, User $user, ?string $uniqueField = null): Model
    {
        $companyId = $user->currentCompany->id;
        $shouldBeEnabled = (bool) ($data['enabled'] ?? false);

        $query = $model::query()
            ->where('company_id', $companyId)
            ->where('enabled', true);

        if ($uniqueField && array_key_exists($uniqueField, $data)) {
            $query->where($uniqueField, $data[$uniqueField]);
        }

        $this->toggleRecords($query, $shouldBeEnabled);

        $data['enabled'] = $shouldBeEnabled;
        $instance = $model->newInstance($data);
        $instance->save();

        return $instance;
    }

    private function toggleRecords(Builder $query, bool &$shouldBeEnabled): void
    {
        if ($shouldBeEnabled) {
            $existingEnabledRecord = $query->first();
            $existingEnabledRecord?->update(['enabled' => false]);
        } elseif ($query->doesntExist()) {
            $shouldBeEnabled = true;
        }
    }
}
