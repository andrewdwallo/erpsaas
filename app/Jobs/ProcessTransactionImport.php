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

    protected Company $company;

    protected Account $account;

    protected BankAccount $bankAccount;

    protected ConnectedBankAccount $connectedBankAccount;

    protected string $startDate;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company, Account $account, BankAccount $bankAccount, ConnectedBankAccount $connectedBankAccount, string $startDate)
    {
        $this->company = $company;
        $this->account = $account;
        $this->bankAccount = $bankAccount;
        $this->connectedBankAccount = $connectedBankAccount;
        $this->startDate = $startDate;
    }

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

        if (count($allTransactions) > 0) {
            $postedTransactions = array_filter($allTransactions, static fn ($transaction) => $transaction->pending === false);
            $currentBalance = $transactionsResponse->accounts[0]->balances->current;

            $transactionService->createStartingBalanceIfNeeded($this->company, $this->account, $this->bankAccount, $postedTransactions, $currentBalance, $startDate);
            $transactionService->storeTransactions($this->company, $this->bankAccount, $postedTransactions);
        }
    }
}
