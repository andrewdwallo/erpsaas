<?php

namespace App\Repositories\Accounting;

use App\Models\Accounting\Account;

class JournalEntryRepository
{
    public function sumAmounts(Account $account, string $type, ?string $startDate = null, ?string $endDate = null): int
    {
        $query = $account->journalEntries()->where('type', $type);

        if ($startDate && $endDate) {
            $query->whereHas('transaction', static function ($query) use ($startDate, $endDate) {
                $query->whereBetween('posted_at', [$startDate, $endDate]);
            });
        } elseif ($startDate) {
            $query->whereHas('transaction', static function ($query) use ($startDate) {
                $query->where('posted_at', '<', $startDate);
            });
        }

        return $query->sum('amount');
    }

    public function sumDebitAmounts(Account $account, string $startDate, ?string $endDate = null): int
    {
        return $this->sumAmounts($account, 'debit', $startDate, $endDate);
    }

    public function sumCreditAmounts(Account $account, string $startDate, ?string $endDate = null): int
    {
        return $this->sumAmounts($account, 'credit', $startDate, $endDate);
    }
}
