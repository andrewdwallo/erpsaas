<?php

namespace App\Policies;

use App\Concerns\SyncsWithCompanyDefaults;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DefaultEnabledRecordPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine whether the company can delete the existing record.
     */
    public function delete(User $user, Model $model): bool
    {
        $hasEnabledRecord = in_array(SyncsWithCompanyDefaults::class, class_uses_recursive($model), true);

        return ! ($hasEnabledRecord && $model->getAttribute('enabled') === true);
    }
}
