<?php

namespace App\Observers;

use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;

class JournalEntryObserver
{
    /**
     * Handle the JournalEntry "created" event.
     */
    public function created(JournalEntry $journalEntry): void
    {
        $account = $journalEntry->account;

        $amount = $this->cleanAmount($journalEntry->amount);

        if ($account) {
            $this->adjustBalance($account, $journalEntry->type, $amount);
        }
    }

    private function cleanAmount($amount): string
    {
        return str_replace(',', '', $amount);
    }

    private function adjustBalance(Account $account, $type, $amount): void
    {
        $debitBalance = $this->cleanAmount($account->debit_balance);
        $creditBalance = $this->cleanAmount($account->credit_balance);

        if ($type === 'debit') {
            $account->debit_balance = bcadd($debitBalance, $amount, 2);
        } elseif ($type === 'credit') {
            $account->credit_balance = bcadd($creditBalance, $amount, 2);
        }

        $this->updateNetMovement($account);
        $this->updateEndingBalance($account);
    }

    private function updateNetMovement(Account $account): void
    {
        $debitBalance = $this->cleanAmount($account->debit_balance);
        $creditBalance = $this->cleanAmount($account->credit_balance);

        $netMovementStrategy = match ($account->category) {
            AccountCategory::Asset, AccountCategory::Expense => bcsub($debitBalance, $creditBalance, 2),
            AccountCategory::Liability, AccountCategory::Equity, AccountCategory::Revenue => bcsub($creditBalance, $debitBalance, 2),
        };

        $account->net_movement = $netMovementStrategy;

        $account->save();
    }

    private function updateEndingBalance(Account $account): void
    {
        $startingBalance = $this->cleanAmount($account->starting_balance);
        $netMovement = $this->cleanAmount($account->net_movement);

        if (in_array($account->category, [AccountCategory::Asset, AccountCategory::Liability, AccountCategory::Equity], true)) {
            $account->ending_balance = bcadd($startingBalance, $netMovement, 2);
        }

        $account->save();
    }

    /**
     * Handle the JournalEntry "deleting" event.
     */
    public function deleting(JournalEntry $journalEntry): void
    {
        $account = $journalEntry->account;

        if ($account) {
            $amount = $this->cleanAmount($journalEntry->amount);

            $this->adjustBalance($account, $journalEntry->type, -$amount);
        }
    }

    /**
     * Handle the JournalEntry "deleted" event.
     */
    public function deleted(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "restored" event.
     */
    public function restored(JournalEntry $journalEntry): void
    {
        //
    }

    /**
     * Handle the JournalEntry "force deleted" event.
     */
    public function forceDeleted(JournalEntry $journalEntry): void
    {
        //
    }
}
