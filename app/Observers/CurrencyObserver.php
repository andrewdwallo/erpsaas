<?php

namespace App\Observers;

use App\Events\CurrencyRateChanged;
use App\Events\DefaultCurrencyChanged;
use App\Models\Setting\Currency;
use Illuminate\Support\Facades\Log;

class CurrencyObserver
{
    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        if ($currency->wasChanged('enabled') && $currency->isEnabled()) {
            event(new DefaultCurrencyChanged($currency));
        }

        if ($currency->wasChanged('rate')) {
            Log::info('Currency rate changed');
            event(new CurrencyRateChanged($currency));
        }
    }

    /**
     * Handle the Currency "deleted" event.
     */
    public function deleted(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "restored" event.
     */
    public function restored(Currency $currency): void
    {
        //
    }

    /**
     * Handle the Currency "force deleted" event.
     */
    public function forceDeleted(Currency $currency): void
    {
        //
    }
}
