<?php

namespace App\Observers;

use App\Events\CurrencyRateChanged;
use App\Events\DefaultCurrencyChanged;
use App\Models\Setting\Currency;

class CurrencyObserver
{
    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        if ($currency->wasChanged('enabled') && $currency->isEnabled()) {
            event(new DefaultCurrencyChanged($currency));
        }

        if ($currency->wasChanged('rate')) {
            event(new CurrencyRateChanged($currency, $currency->getOriginal('rate'), $currency->rate));
        }
    }
}
