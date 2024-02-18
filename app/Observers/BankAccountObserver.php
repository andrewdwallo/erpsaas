<?php

namespace App\Observers;

use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;

class BankAccountObserver
{
    /**
     * Handle the BankAccount "created" event.
     */
    public function created(BankAccount $bankAccount): void
    {
        //
    }

    /**
     * Handle the BankAccount "creating" event.
     */
    public function creating(BankAccount $bankAccount): void
    {
        //
    }

    /**
     * Get the default bank account subtype.
     */
    protected function getDefaultBankAccountSubtype(int $companyId, AccountType $type)
    {
        $subType = AccountSubtype::where('company_id', $companyId)
            ->where('name', 'Cash and Cash Equivalents')
            ->where('type', $type)
            ->first();

        if (! $subType) {
            $subType = AccountSubtype::where('company_id', $companyId)
                ->where('type', $type)
                ->first();
        }

        return $subType?->id;
    }

    /**
     * Handle the BankAccount "updated" event.
     */
    public function updated(BankAccount $bankAccount): void
    {
        //
    }

    /**
     * Handle the BankAccount "deleted" event.
     */
    public function deleted(BankAccount $bankAccount): void
    {
        //
    }

    /**
     * Handle the BankAccount "restored" event.
     */
    public function restored(BankAccount $bankAccount): void
    {
        //
    }

    /**
     * Handle the BankAccount "force deleted" event.
     */
    public function forceDeleted(BankAccount $bankAccount): void
    {
        //
    }
}
