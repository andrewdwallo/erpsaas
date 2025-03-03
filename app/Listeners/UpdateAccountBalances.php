<?php

namespace App\Listeners;

use App\Enums\Accounting\TransactionType;
use App\Events\CurrencyRateChanged;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Support\Facades\DB;

class UpdateAccountBalances
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CurrencyRateChanged $event): void
    {
        DB::transaction(static function () use ($event) {
            $bankAccounts = BankAccount::with('account')
                ->whereHas('account', static function ($query) use ($event) {
                    $query->where('currency_code', $event->currency->code);
                })
                ->get();

            foreach ($bankAccounts as $bankAccount) {
                /** @var BankAccount $bankAccount */
                $account = $bankAccount->account;

                $oldConvertedBalanceInCents = $account->ending_balance->convert()->getConvertedAmount();
                $ratio = $event->newRate / $event->oldRate;
                $newConvertedBalance = bcmul($oldConvertedBalanceInCents, $ratio, 2);
                $newConvertedBalanceInCents = (int) round($newConvertedBalance);

                $differenceInCents = $newConvertedBalanceInCents - $oldConvertedBalanceInCents;

                if ($differenceInCents !== 0) {
                    $gainOrLossAccountName = $differenceInCents > 0 ? 'Gain on Foreign Exchange' : 'Loss on Foreign Exchange';
                    $gainOrLossAccount = Account::where('name', $gainOrLossAccountName)->first();
                    $transactionType = $differenceInCents > 0 ? TransactionType::Deposit : TransactionType::Withdrawal;
                    $description = "Exchange rate adjustment due to rate change from {$event->oldRate} to {$event->newRate}";
                    $absoluteDifferenceAmountInCents = abs($differenceInCents);
                    $formattedSimpleDifference = CurrencyConverter::prepareForMutator($absoluteDifferenceAmountInCents, $bankAccount->account->currency_code);

                    Transaction::create([
                        'company_id' => $account->company_id,
                        'account_id' => $gainOrLossAccount->id,
                        'bank_account_id' => $bankAccount->id,
                        'type' => $transactionType,
                        'amount' => $formattedSimpleDifference,
                        'payment_channel' => 'other',
                        'posted_at' => today(),
                        'description' => $description,
                        'pending' => false,
                        'reviewed' => false,
                    ]);
                }
            }
        });
    }
}
