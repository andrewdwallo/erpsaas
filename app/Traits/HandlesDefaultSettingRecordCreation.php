<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HandlesDefaultSettingRecordCreation
{
    abstract protected function getRelatedEntities(): array;
    abstract protected function getFormModel(): string;

    protected function handleRecordCreation(array $data): Model
    {
        $relatedEntities = $this->getRelatedEntities();

        $model = $this->getFormModel();

        $existingRecord = $model::where('company_id', Auth::user()->currentCompany->id)
            ->latest()
            ->first();

        $newData = [
            'company_id' => Auth::user()->currentCompany->id,
            'updated_by' => Auth::id(),
        ];

        foreach ($relatedEntities as $field => $params) {
            [$class, $key, $type] = array_pad($params, 3, null);

            if ($existingRecord === null || !isset($existingRecord->{$field})) {
                $newData[$field] = $data[$field];
                continue;
            }

            if (isset($data[$field]) && $data[$field] !== $existingRecord->{$field}) {
                $this->updateEnabledRecord($class, $key, $existingRecord->{$field}, $type, false);
                $this->updateEnabledRecord($class, $key, $data[$field], $type, true);

                $newData[$field] = $data[$field];
            } else {
                $newData[$field] = $existingRecord->{$field};
            }
        }

        return $model::create($newData);
    }

    protected function updateEnabledRecord($class, $key, $value, $type = null, $enabled = true): void
    {
        $query = $class::where('company_id', Auth::user()->currentCompany->id)
            ->where('enabled', !$enabled);

        if ($type !== null) {
            $query = $query->where('type', $type);
        }

        $query->where($key, $value)
            ->update([
                'enabled' => $enabled,
                'updated_by' => Auth::id(),
            ]);
    }
}

