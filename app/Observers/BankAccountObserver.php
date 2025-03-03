<?php

namespace App\Observers;

use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use Illuminate\Support\Facades\DB;

class BankAccountObserver
{
    /**
     * Handle the BankAccount "deleting" event.
     */
    public function deleting(BankAccount $bankAccount): void
    {
        DB::transaction(function () use ($bankAccount) {
            $account = $bankAccount->account;
            $connectedBankAccount = $bankAccount->connectedBankAccount;

            if ($account) {
                $bankAccount->transactions()->each(fn (Transaction $transaction) => $transaction->delete());
                $account->delete();
            }

            if ($connectedBankAccount) {
                $connectedBankAccount->delete();
            }
        });
    }
}
