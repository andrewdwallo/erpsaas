<?php

namespace App\Traits;

use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HandlesResourceRecordUpdate
{
    protected function handleRecordUpdateWithUniqueField(Model $record, array $data, User $user, ?string $uniqueField = null, ?array $evaluatedTypes = null): Model
    {
        if (is_array($evaluatedTypes)) {
            $evaluatedTypes = $this->ensureUpdateEnumValues($evaluatedTypes);
        }

        if ($uniqueField && ! in_array($data[$uniqueField] ?? '', $evaluatedTypes ?? [], true)) {
            $data['enabled'] = false;

            return tap($record)->update($data);
        }

        $companyId = $user->currentCompany->id;
        $oldValue = $uniqueField ? $record->{$uniqueField} : null;
        $newValue = $uniqueField ? $data[$uniqueField] : null;
        $enabled = (bool) ($data['enabled'] ?? false);
        $wasOriginallyEnabled = (bool) $record->getAttribute('enabled');

        if ($oldValue instanceof BackedEnum) {
            $oldValue = $oldValue->value;
        }

        if ($newValue instanceof BackedEnum) {
            $newValue = $newValue->value;
        }

        if ($uniqueField && $oldValue !== $newValue && $wasOriginallyEnabled) {
            $newValue = $oldValue;
            $data[$uniqueField] = $oldValue;
        }

        if ($enabled === true && ! $wasOriginallyEnabled) {
            $this->toggleRecord($companyId, $record, $uniqueField, $newValue, true, false);
        } elseif ($enabled === false && $wasOriginallyEnabled) {
            $enabled = true;
        }

        $data['enabled'] = $enabled;

        return tap($record)->update($data);
    }

    private function ensureUpdateEnumValues(array $evaluatedTypes): array
    {
        return array_map(static function ($type) {
            return $type instanceof BackedEnum ? $type->value : $type;
        }, $evaluatedTypes);
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
}
