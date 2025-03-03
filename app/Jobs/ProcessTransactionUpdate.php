<?php

namespace App\Jobs;

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

class ProcessTransactionUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected Company $company,
        protected string $itemId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PlaidService $plaidService, TransactionService $transactionService): void
    {
        $connectedBankAccounts = $this->company->connectedBankAccounts()
            ->where('item_id', $this->itemId)
            ->where('import_transactions', true)
            ->get();

        foreach ($connectedBankAccounts as $connectedBankAccount) {
            /** @var ConnectedBankAccount $connectedBankAccount */
            $accessToken = $connectedBankAccount->access_token;
            $bankAccount = $connectedBankAccount->bankAccount;
            $allTransactions = [];
            $offset = 0;

            $bufferDays = 15;
            $lastImportedAtDate = Carbon::parse($connectedBankAccount->last_imported_at);
            $startDate = $lastImportedAtDate->subDays($bufferDays)->toDateString();
            $endDate = Carbon::now()->toDateString();

            $transactionsResponse = $plaidService->getTransactions($accessToken, $startDate, $endDate, [
                'account_ids' => [$connectedBankAccount->external_account_id],
            ]);

            $allTransactions = [...$allTransactions, ...$transactionsResponse->transactions];
            $totalTransactions = $transactionsResponse->total_transactions;

            while (count($allTransactions) < $totalTransactions) {
                $offset += count($transactionsResponse->transactions);
                $transactionsResponse = $plaidService->getTransactions($accessToken, $startDate, $endDate, [
                    'account_ids' => [$connectedBankAccount->external_account_id],
                    'offset' => $offset,
                ]);

                $allTransactions = [...$allTransactions, ...$transactionsResponse->transactions];
            }

            $existingTransactionIds = $bankAccount->transactions()->pluck('plaid_transaction_id')->toArray();
            $newTransactions = array_filter($allTransactions, static function ($transaction) use ($existingTransactionIds) {
                return ! in_array($transaction->transaction_id, $existingTransactionIds, true) && $transaction->pending === false;
            });

            if (count($newTransactions) > 0) {
                $transactionService->storeTransactions($this->company, $bankAccount, $newTransactions);

                $connectedBankAccount->update([
                    'last_imported_at' => Carbon::now(),
                ]);
            }
        }
    }
}
