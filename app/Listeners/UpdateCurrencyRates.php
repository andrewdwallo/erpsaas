<?php

namespace App\Listeners;

use App\Events\DefaultCurrencyChanged;
use App\Models\Setting\Currency;
use App\Services\CurrencyService;

class UpdateCurrencyRates
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
    public function handle(DefaultCurrencyChanged $event): void
    {
        $currencyService = app(CurrencyService::class);

        $currencies = Currency::where('code', '!=', $event->currency->code)->get();

        foreach ($currencies as $currency) {
            $newRate = $currencyService->getCachedExchangeRate($event->currency->code, $currency->code);

            if ($newRate !== null) {
                $currency->update(['rate' => $newRate]);
            }
        }
    }
}
