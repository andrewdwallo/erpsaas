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

    public int | string $selectedBankAccountId;

    public string $startDate;

    /**
     * Create a new event instance.
     */
    public function __construct(Company $company, ConnectedBankAccount $connectedBankAccount, int | string $selectedBankAccountId, string $startDate)
    {
        $this->company = $company;
        $this->connectedBankAccount = $connectedBankAccount;
        $this->selectedBankAccountId = $selectedBankAccountId;
        $this->startDate = $startDate;
    }
}
