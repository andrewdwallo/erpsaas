<?php

namespace App\Observers;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $chartAccount = $transaction->account;
        $bankAccount = $transaction->bankAccount->account;

        $debitAccount = $transaction->type === 'expense' ? $chartAccount : $bankAccount;
        $creditAccount = $transaction->type === 'expense' ? $bankAccount : $chartAccount;

        $this->createJournalEntries($transaction, $debitAccount, $creditAccount);
    }

    private function createJournalEntries(Transaction $transaction, Account $debitAccount, Account $creditAccount): void
    {
        $debitAccount->journalEntries()->create([
            'company_id' => $transaction->company_id,
            'transaction_id' => $transaction->id,
            'type' => 'debit',
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ]);

        $creditAccount->journalEntries()->create([
            'company_id' => $transaction->company_id,
            'transaction_id' => $transaction->id,
            'type' => 'credit',
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ]);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $changes = $transaction->getChanges();

        $relevantChanges = array_intersect_key($changes, array_flip(['amount', 'description', 'account_id', 'bank_account_id', 'type']));

        if (empty($relevantChanges)) {
            return;
        }

        $chartAccount = $transaction->account;
        $bankAccount = $transaction->bankAccount->account;

        $journalEntries = $transaction->journalEntries;

        $debitEntry = $journalEntries->where('type', 'debit')->first();
        $creditEntry = $journalEntries->where('type', 'credit')->first();

        $debitAccount = $transaction->type === 'expense' ? $chartAccount : $bankAccount;
        $creditAccount = $transaction->type === 'expense' ? $bankAccount : $chartAccount;

        $debitEntry?->update([
            'account_id' => $debitAccount->id,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ]);

        $creditEntry?->update([
            'account_id' => $creditAccount->id,
            'amount' => $transaction->amount,
            'description' => $transaction->description,
        ]);
    }

    /**
     * Handle the Transaction "deleting" event.
     */
    public function deleting(Transaction $transaction): void
    {
        DB::transaction(static function () use ($transaction) {
            $transaction->journalEntries()->each(fn (JournalEntry $entry) => $entry->delete());
        });
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        //
    }
}
