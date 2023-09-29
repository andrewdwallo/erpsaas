<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HandlesCompanyDefaultUpdate
{
    abstract protected function getRelatedEntities(): array;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $relatedEntities = $this->getRelatedEntities();

        $existingRecord = $record::query()->where('company_id', Auth::user()->currentCompany->id)
            ->latest()
            ->first();

        foreach ($relatedEntities as $field => $params) {
            [$modelClass, $key, $type] = array_pad($params, 3, null);

            if ($existingRecord === null || ! isset($existingRecord->{$field})) {
                continue;
            }

            if (isset($data[$field]) && $data[$field] !== $existingRecord->{$field}) {
                $this->updateEnabledRecord(new $modelClass, $key, $existingRecord->{$field}, $type, false);
                $this->updateEnabledRecord(new $modelClass, $key, $data[$field], $type);
            }
        }

        $defaults = $record::query()->where('company_id', Auth::user()->currentCompany->id)->first();

        if ($defaults === null) {
            $defaults = $record::query()->create($data);
        } else {
            $defaults->update($data);
        }

        return $defaults;
    }

    protected function updateEnabledRecord(Model $record, string $key, mixed $value, ?string $type = null, bool $enabled = true): void
    {
        $query = $record::query()->where('company_id', Auth::user()->currentCompany->id)
            ->where('enabled', ! $enabled);

        if ($type !== null) {
            $query = $query->where('type', $type);
        }

        $query->where($key, $value)
            ->update(compact('enabled'));
    }
}
