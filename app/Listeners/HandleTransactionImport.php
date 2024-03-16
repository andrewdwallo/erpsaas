<?php

namespace App\Listeners;

use App\Events\StartTransactionImport;
use App\Jobs\ProcessTransactionImport;
use App\Services\ConnectedBankAccountService;
use Illuminate\Support\Facades\DB;

class HandleTransactionImport
{
    protected ConnectedBankAccountService $connectedBankAccountService;

    /**
     * Create the event listener.
     */
    public function __construct(ConnectedBankAccountService $connectedBankAccountService)
    {
        $this->connectedBankAccountService = $connectedBankAccountService;
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

        $bankAccount = $this->connectedBankAccountService->getOrProcessBankAccountForConnectedBankAccount($company, $connectedBankAccount, $selectedBankAccountId);
        $account = $this->connectedBankAccountService->getOrProcessAccountForConnectedBankAccount($bankAccount, $company, $connectedBankAccount);

        $connectedBankAccount->update([
            'bank_account_id' => $bankAccount->id,
            'import_transactions' => true,
        ]);

        ProcessTransactionImport::dispatch(
            $company,
            $account,
            $bankAccount,
            $connectedBankAccount,
            $startDate,
        )->onQueue('transactions');
    }
}
