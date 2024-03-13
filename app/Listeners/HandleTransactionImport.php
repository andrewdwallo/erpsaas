<?php

namespace App\Listeners;

use App\Events\StartTransactionImport;
use App\Models\Accounting\Account;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Services\ConnectedBankAccountService;
use App\Services\PlaidService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HandleTransactionImport
{
    protected PlaidService $plaid;

    protected ConnectedBankAccountService $connectedBankAccountService;

    protected TransactionService $transactionService;

    /**
     * Create the event listener.
     */
    public function __construct(PlaidService $plaid, ConnectedBankAccountService $connectedBankAccountService, TransactionService $transactionService)
    {
        $this->plaid = $plaid;
        $this->connectedBankAccountService = $connectedBankAccountService;
        $this->transactionService = $transactionService;
    }

    /**
     * Handle the event.
     */
    public function handle(StartTransactionImport $event): void
    {
        DB::transaction(function () use ($event) {
            $this->processTransactionImport($event);
        });
    }

    public function processTransactionImport(StartTransactionImport $event): void
    {
        $company = $event->company;
        $connectedBankAccount = $event->connectedBankAccount;
        $selectedBankAccountId = $event->selectedBankAccountId;
        $startDate = $event->startDate;

        $accessToken = $connectedBankAccount->access_token;

        $bankAccount = $this->connectedBankAccountService->getOrProcessBankAccountForConnectedBankAccount($company, $connectedBankAccount, $selectedBankAccountId);
        $account = $this->connectedBankAccountService->getOrProcessAccountForConnectedBankAccount($bankAccount, $company, $connectedBankAccount);

        $connectedBankAccount->update([
            'bank_account_id' => $bankAccount->id,
            'import_transactions' => true,
        ]);

        $this->processTransactions($company, $account, $bankAccount, $connectedBankAccount, $startDate, $accessToken);
    }

    public function processTransactions(Company $company, Account $account, BankAccount $bankAccount, ConnectedBankAccount $connectedBankAccount, string $startDate, string $accessToken): void
    {
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::parse($startDate)->toDateString();

        $transactionsResponse = $this->plaid->getTransactions($accessToken, $startDate, $endDate, [
            'account_ids' => [$connectedBankAccount->external_account_id],
        ]);

        if (filled($transactionsResponse->transactions)) {
            $postedTransactions = array_filter($transactionsResponse->transactions, static fn ($transaction) => $transaction->pending === false);
            $transactions = array_reverse($postedTransactions);
            $currentBalance = $transactionsResponse->accounts[0]->balances->current;

            $this->transactionService->createStartingBalanceIfNeeded($company, $account, $bankAccount, $transactions, $currentBalance, $startDate);
            $this->transactionService->storeTransactions($company, $bankAccount, $transactions);
        }
    }
}
