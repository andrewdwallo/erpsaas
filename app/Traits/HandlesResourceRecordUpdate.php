<?php

namespace App\Traits;

use App\Models\User;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HandlesResourceRecordUpdate
{
    /**
     * @throws Halt
     */
    protected function handleRecordUpdateWithUniqueField(Model $record, array $data, User $user, string|null $uniqueField = null): Model
    {
        try {
            return DB::transaction(function () use ($user, $uniqueField, $record, $data) {
                $companyId = $user->currentCompany->id;
                $oldValue = $uniqueField ? $record->{$uniqueField} : null;
                $newValue = $uniqueField ? $data[$uniqueField] : null;
                $enabled = (bool)($data['enabled'] ?? false);

                if ($oldValue !== $newValue && $record->enabled) {
                    $this->toggleRecord($companyId, $record, $uniqueField, $oldValue, false, true);
                }

                if ($enabled === true) {
                    $this->toggleRecord($companyId, $record, $uniqueField, $newValue, true, false);
                } elseif ($enabled === false) {
                    $this->ensureAtLeastOneEnabled($companyId, $record, $uniqueField, $newValue, $enabled);
                }

                $data['enabled'] = $enabled;

                return tap($record)->update($data);
            });
        } catch (ValidationException) {
            throw new Halt('Invalid data provided. Please check the form and try again.');
        } catch (AuthorizationException) {
            throw new Halt('You are not authorized to perform this action.');
        } catch (Throwable) {
            throw new Halt('An unexpected error occurred. Please try again.');
        }
    }

    protected function toggleRecord(int $companyId, Model $record, ?string $uniqueField, $value, bool $enabled, bool $newStatus): void
    {
        $query = $this->buildQuery($companyId, $record, $uniqueField, $value, $enabled);

        if ($newStatus) {
            $otherRecord = $query->first();

            if ($otherRecord) {
                $otherRecord->enabled = true;
                $otherRecord->save();
            }
        } else {
            $query->update(['enabled' => false]);
        }
    }

    protected function buildQuery(int $companyId, Model $record, ?string $uniqueField, $value, bool $enabled): Builder
    {
        $query = $record::query()->where('company_id', $companyId)
            ->where('id', '!=', $record->id)
            ->where('enabled', $enabled);

        if($uniqueField){
            $query->where($uniqueField, $value);
        }

        return $query;
    }

    protected function ensureAtLeastOneEnabled(int $companyId, Model $record, ?string $uniqueField, $value, bool &$enabled): void
    {
        $query = $this->buildQuery($companyId, $record, $uniqueField, $value, true);
        $enabledCount = $query->count();

        if ($enabledCount === 0) {
            $enabled = true;
        }
    }
}
