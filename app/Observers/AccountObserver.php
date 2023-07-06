<?php

namespace App\Observers;

use App\Models\Banking\Account;
use Illuminate\Support\Facades\Auth;

class AccountObserver
{
    /**
     * Handle the account "creating" event.
     */
    public function creating(Account $account): void
    {
        $account->company()->associate(Auth::user()->currentCompany->id);
        $account->company_id = Auth::user()->currentCompany->id;
        $account->created_by = Auth::id();
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "updating" event.
     */
    public function updating(Account $account): void
    {
        $account->updated_by = Auth::id();
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}
