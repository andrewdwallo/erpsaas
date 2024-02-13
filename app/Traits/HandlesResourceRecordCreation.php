<?php

namespace App\Traits;

use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HandlesResourceRecordCreation
{
    protected function handleRecordCreationWithUniqueField(array $data, Model $model, User $user, ?string $uniqueField = null, ?array $evaluatedTypes = null): Model
    {
        if (is_array($evaluatedTypes)) {
            $evaluatedTypes = $this->ensureCreationEnumValues($evaluatedTypes);
        }

        if ($uniqueField && ! in_array($data[$uniqueField] ?? '', $evaluatedTypes ?? [], true)) {
            $data['enabled'] = false;
            $instance = $model->newInstance($data);
            $instance->save();

            return $instance;
        }

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

    private function ensureCreationEnumValues(array $evaluatedTypes): array
    {
        return array_map(static function ($type) {
            return $type instanceof BackedEnum ? $type->value : $type;
        }, $evaluatedTypes);
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
