<?php

namespace App\Listeners;

use App\Events\StartTransactionImport;
use App\Models\Accounting\Account;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use App\Services\AccountService;
use App\Services\BankAccountService;
use App\Services\PlaidService;
use App\Services\TransactionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HandleTransactionImport
{
    protected PlaidService $plaid;

    protected BankAccountService $bankAccountService;

    protected AccountService $accountService;

    protected TransactionService $transactionService;

    /**
     * Create the event listener.
     */
    public function __construct(PlaidService $plaid, BankAccountService $bankAccountService, AccountService $accountService, TransactionService $transactionService)
    {
        $this->plaid = $plaid;
        $this->bankAccountService = $bankAccountService;
        $this->accountService = $accountService;
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

        $bankAccount = $this->bankAccountService->getOrProcessBankAccount($company, $connectedBankAccount, $selectedBankAccountId);
        $account = $this->accountService->getOrProcessAccount($bankAccount, $company, $connectedBankAccount);

        $connectedBankAccount->update([
            'bank_account_id' => $bankAccount->id,
            'import_transactions' => true,
        ]);

        $this->processTransactions($startDate, $company, $connectedBankAccount, $accessToken, $account);
    }

    public function processTransactions(string $startDate, Company $company, ConnectedBankAccount $connectedBankAccount, string $accessToken, Account $account): void
    {
        $endDate = Carbon::now()->toDateString();
        $startDate = Carbon::parse($startDate)->toDateString();

        $transactionsResponse = $this->plaid->getTransactions($accessToken, $startDate, $endDate, [
            'account_ids' => [$connectedBankAccount->external_account_id],
        ]);

        if (filled($transactionsResponse->transactions)) {
            $transactions = array_reverse($transactionsResponse->transactions);
            $currentBalance = $transactionsResponse->accounts[0]->balances->current;

            $this->transactionService->createStartingBalanceIfNeeded($company, $account, $connectedBankAccount, $transactions, $currentBalance, $startDate);
            $this->transactionService->storeTransactions($company, $account, $connectedBankAccount, $transactions);
        }
    }
}
