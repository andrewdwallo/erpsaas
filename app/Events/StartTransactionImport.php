<?php

namespace App\Events;

use App\Models\Banking\ConnectedBankAccount;
use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartTransactionImport
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Company $company,
        public ConnectedBankAccount $connectedBankAccount,
        public int | string $selectedBankAccountId,
        public string $startDate
    ) {}
}
