<?php

namespace App\Jobs;

use App\Models\Accounting\Account;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Services\PlaidService;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ProcessTransactionImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Company $company,
        protected Account $account,
        protected BankAccount $bankAccount,
        protected ConnectedBankAccount $connectedBankAccount,
        protected string $startDate
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaid, TransactionService $transactionService): void
    {
        $accessToken = $this->connectedBankAccount->access_token;
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::parse($this->startDate)->toDateString();
        $allTransactions = [];
        $offset = 0;

        $transactionsResponse = $plaid->getTransactions($accessToken, $startDate, $endDate, [
            'account_ids' => [$this->connectedBankAccount->external_account_id],
        ]);

        $allTransactions = [...$allTransactions, ...$transactionsResponse->transactions];
        $totalTransactions = $transactionsResponse->total_transactions;

        while (count($allTransactions) < $totalTransactions) {
            $offset += count($transactionsResponse->transactions);
            $transactionsResponse = $plaid->getTransactions($accessToken, $startDate, $endDate, [
                'account_ids' => [$this->connectedBankAccount->external_account_id],
                'offset' => $offset,
            ]);

            $allTransactions = [...$allTransactions, ...$transactionsResponse->transactions];
        }

        $existingTransactionIds = $this->bankAccount->transactions->pluck('plaid_transaction_id')->toArray();
        $newTransactions = array_filter($allTransactions, static function ($transaction) use ($existingTransactionIds) {
            return ! in_array($transaction->transaction_id, $existingTransactionIds) && $transaction->pending === false;
        });

        if (count($newTransactions) > 0) {
            $currentBalance = $transactionsResponse->accounts[0]->balances->current;

            $transactionService->createStartingBalanceIfNeeded($this->company, $this->account, $this->bankAccount, $newTransactions, $currentBalance, $startDate);
            $transactionService->storeTransactions($this->company, $this->bankAccount, $newTransactions);

            $this->connectedBankAccount->update([
                'last_imported_at' => Carbon::now(),
            ]);
        }
    }
}
