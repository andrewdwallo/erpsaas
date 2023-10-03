<?php

namespace App\Events;

use App\Models\Setting\Currency;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DefaultCurrencyChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Currency $currency;

    /**
     * Create a new event instance.
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }
}
