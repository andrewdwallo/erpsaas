<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartTransactionImport
{
    use Dispatchable, SerializesModels;

    public Company $company;
    public $connectedBankAccountId;
    public $selectedBankAccountId;
    public $startDate;

    /**
     * Create a new event instance.
     */
    public function __construct($company, $connectedBankAccountId, $selectedBankAccountId, $startDate)
    {
        $this->company = $company;
        $this->connectedBankAccountId = $connectedBankAccountId;
        $this->selectedBankAccountId = $selectedBankAccountId;
        $this->startDate = $startDate;
    }
}
