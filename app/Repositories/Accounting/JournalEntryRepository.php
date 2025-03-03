<?php

namespace App\Repositories\Accounting;

use App\Models\Accounting\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class JournalEntryRepository
{
    public function sumAmounts(Account $account, string $type, ?string $startDate = null, ?string $endDate = null): int
    {
        $query = $account->journalEntries()->where('type', $type);

        $startDateCarbon = Carbon::parse($startDate)->startOfDay();
        $endDateCarbon = Carbon::parse($endDate)->endOfDay();

        if ($startDate && $endDate) {
            $query->whereHas('transaction', static function (Builder $query) use ($startDateCarbon, $endDateCarbon) {
                $query->whereBetween('posted_at', [$startDateCarbon, $endDateCarbon]);
            });
        } elseif ($startDate) {
            $query->whereHas('transaction', static function (Builder $query) use ($startDateCarbon) {
                $query->where('posted_at', '<=', $startDateCarbon);
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
