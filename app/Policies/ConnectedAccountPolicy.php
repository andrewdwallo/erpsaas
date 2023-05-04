<?php

namespace App\Policies;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConnectedAccountPolicy
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
    public function view(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
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
    public function update(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
    }
}
