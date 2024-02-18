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

    public Company $company;

    public ConnectedBankAccount $connectedBankAccount;

    public mixed $selectedBankAccountId;

    public mixed $startDate;

    /**
     * Create a new event instance.
     */
    public function __construct($company, $connectedBankAccount, $selectedBankAccountId, $startDate)
    {
        $this->company = $company;
        $this->connectedBankAccount = $connectedBankAccount;
        $this->selectedBankAccountId = $selectedBankAccountId;
        $this->startDate = $startDate;
    }
}
