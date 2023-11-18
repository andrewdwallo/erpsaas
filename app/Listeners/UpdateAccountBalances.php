<?php

namespace App\Listeners;

use App\Events\CurrencyRateChanged;
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
            $accounts = $event->currency->accounts;

            foreach ($accounts as $account) {
                $initialHistory = $account->histories()->where('account_id', $account->id)
                    ->orderBy('created_at')
                    ->first();

                if ($initialHistory) {
                    $originalBalance = $initialHistory->balance;
                    $originalBalance = money($originalBalance, $account->currency->code)->getAmount();
                    $originalRate = $initialHistory->exchange_rate;
                    $precision = $account->currency->precision;

                    $newRate = $event->currency->rate;
                    $newBalance = ($newRate / $originalRate) * $originalBalance;

                    $newBalanceScaled = round($newBalance, $precision);

                    $account->balance = $newBalanceScaled;
                    $account->save();
                }
            }
        });
    }
}
