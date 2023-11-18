<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyConfigured
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Company $company;

    /**
     * Create a new event instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }
}
