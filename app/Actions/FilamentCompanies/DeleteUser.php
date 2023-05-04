<?php

namespace App\Actions\FilamentCompanies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Wallo\FilamentCompanies\Contracts\DeletesCompanies;
use Wallo\FilamentCompanies\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * The company deleter implementation.
     */
    protected DeletesCompanies $deletesCompanies;

    /**
     * Create a new action instance.
     */
    public function __construct(DeletesCompanies $deletesCompanies)
    {
        $this->deletesCompanies = $deletesCompanies;
    }

    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->deleteCompanies($user);
            $user->deleteProfilePhoto();
            $user->connectedAccounts->each(static fn ($account) => $account->delete());
            $user->tokens->each(static fn ($token) => $token->delete());
            $user->delete();
        });
    }

    /**
     * Delete the companies and company associations attached to the user.
     */
    protected function deleteCompanies(User $user): void
    {
        $user->companies()->detach();

        $user->ownedCompanies->each(function (Company $company) {
            $this->deletesCompanies->delete($company);
        });
    }
}
