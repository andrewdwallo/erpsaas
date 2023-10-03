<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\{Builder, Model};

trait HandlesResourceRecordUpdate
{
    protected function handleRecordUpdateWithUniqueField(Model $record, array $data, User $user, ?string $uniqueField = null): Model
    {
        $companyId = $user->currentCompany->id;
        $oldValue = $uniqueField ? $record->{$uniqueField} : null;
        $newValue = $uniqueField ? $data[$uniqueField] : null;
        $enabled = (bool) ($data['enabled'] ?? false);

        if ($oldValue !== $newValue && $record->getAttribute('enabled')) {
            $this->toggleRecord($companyId, $record, $uniqueField, $oldValue, false, true);
        }

        if ($enabled === true) {
            $this->toggleRecord($companyId, $record, $uniqueField, $newValue, true, false);
        } elseif ($enabled === false) {
            $this->ensureAtLeastOneEnabled($companyId, $record, $uniqueField, $newValue, $enabled);
        }

        $data['enabled'] = $enabled;

        return tap($record)->update($data);
    }

    protected function toggleRecord(int $companyId, Model $record, ?string $uniqueField, $value, bool $enabled, bool $newStatus): void
    {
        $query = $this->buildQuery($companyId, $record, $uniqueField, $value, $enabled);

        if ($newStatus && ($otherRecord = $query->first())) {
            $otherRecord->update(['enabled' => true]);
        } else {
            $query->update(['enabled' => false]);
        }
    }

    protected function buildQuery(int $companyId, Model $record, ?string $uniqueField, $value, bool $enabled): Builder
    {
        return $record::query()
            ->where('company_id', $companyId)
            ->where('id', '!=', $record->getKey())
            ->where('enabled', $enabled)
            ->when($uniqueField, static fn ($q) => $q->where($uniqueField, $value));
    }

    protected function ensureAtLeastOneEnabled(int $companyId, Model $record, ?string $uniqueField, $value, bool &$enabled): void
    {
        $query = $this->buildQuery($companyId, $record, $uniqueField, $value, true);
        $enabled = $query->exists() ? $enabled : true;
    }
}
