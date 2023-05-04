<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        return $user->belongsToCompany($company);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->ownsCompany($company);
    }

    /**
     * Determine whether the user can add company employees.
     */
    public function addCompanyEmployee(User $user, Company $company): bool
    {
        return $user->ownsCompany($company);
    }

    /**
     * Determine whether the user can update company employee permissions.
     */
    public function updateCompanyEmployee(User $user, Company $company): bool
    {
        return $user->ownsCompany($company);
    }

    /**
     * Determine whether the user can remove company employees.
     */
    public function removeCompanyEmployee(User $user, Company $company): bool
    {
        return $user->ownsCompany($company);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->ownsCompany($company);
    }
}
