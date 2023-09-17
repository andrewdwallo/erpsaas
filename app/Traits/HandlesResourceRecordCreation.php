<?php

namespace App\Traits;

use App\Models\User;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HandlesResourceRecordCreation
{
    /**
     * @throws Halt
     */
    protected function handleRecordCreationWithUniqueField(array $data, Model $model, User $user, string|null $uniqueField = null, ?string $uniqueFieldValue = null): Model
    {
        try {
            return DB::transaction(function () use ($data, $user, $model, $uniqueField, $uniqueFieldValue) {
                $enabled = (bool)($data['enabled'] ?? false);

                if ($enabled === true) {
                    $this->disableExistingRecord($user->currentCompany->id, $model, $uniqueField, $uniqueFieldValue);
                } else {
                    $this->ensureAtLeastOneEnabled($user->currentCompany->id, $model, $enabled, $uniqueField, $uniqueFieldValue);
                }

                $data['enabled'] = $enabled;

                return $model::create($data);
            });
        } catch (ValidationException) {
            throw new Halt('Invalid data provided. Please check the form and try again.');
        } catch (AuthorizationException) {
            throw new Halt('You are not authorized to perform this action.');
        } catch (Throwable) {
            throw new Halt('An unexpected error occurred. Please try again.');
        }
    }

    protected function disableExistingRecord(int $companyId, Model $model, string|null $uniqueField = null, string|null $uniqueFieldValue = null): void
    {
        $query = $model::query()->where('company_id', $companyId)
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
        $query = $model::query()->where('company_id', $companyId)
            ->where('enabled', true);

        if($uniqueField && $uniqueFieldValue){
            $query->where($uniqueField, $uniqueFieldValue);
        }

        $otherEnabledRecord = $query->first();

        if ($otherEnabledRecord === null) {
            $enabled = true;
        }
    }
}
