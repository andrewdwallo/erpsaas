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

        if ($account) {
            $this->adjustBalance($account, $journalEntry->type, $journalEntry->amount);
        }
    }

    private function updateEndingBalance(Account $account): void
    {
        $netMovementStrategy = match ($account->category) {
            AccountCategory::Asset, AccountCategory::Expense => $account->debit_balance - $account->credit_balance,
            AccountCategory::Liability, AccountCategory::Equity, AccountCategory::Revenue => $account->credit_balance - $account->debit_balance,
        };

        $account->net_movement = $netMovementStrategy;

        if (in_array($account->category, [AccountCategory::Asset, AccountCategory::Liability, AccountCategory::Equity], true)) {
            $account->ending_balance = $account->starting_balance + $account->net_movement;
        }

        $account->save();
    }

    /**
     * Handle the JournalEntry "updated" event.
     */
    public function updated(JournalEntry $journalEntry): void
    {
        $accountChanged = $journalEntry->wasChanged('account_id');
        $amountChanged = $journalEntry->wasChanged('amount');
        $typeChanged = $journalEntry->wasChanged('type');

        $originalAccountId = $journalEntry->getOriginal('account_id');
        $originalAmount = $journalEntry->getOriginal('amount');
        $originalType = $journalEntry->getOriginal('type');

        if ($accountChanged || $amountChanged || $typeChanged) {
            // Revert the effects of the original journal entry
            $originalAccount = Account::find($originalAccountId);

            if ($originalAccount) {
                $this->adjustBalance($originalAccount, $originalType, -$originalAmount);
            }
        }

        $newAccount = ($accountChanged) ? Account::find($journalEntry->account_id) : $journalEntry->account;

        if ($newAccount) {
            $this->adjustBalance($newAccount, $journalEntry->type, $journalEntry->amount);
        }
    }

    private function adjustBalance(Account $account, $type, $amount): void
    {
        if ($type === 'debit') {
            $account->debit_balance += $amount;
        } elseif ($type === 'credit') {
            $account->credit_balance += $amount;
        }

        $this->updateEndingBalance($account);
    }

    /**
     * Handle the JournalEntry "deleted" event.
     */
    public function deleted(JournalEntry $journalEntry): void
    {
        $account = $journalEntry->account;

        if ($account) {
            $this->adjustBalance($account, $journalEntry->type, -$journalEntry->amount);
        }
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
