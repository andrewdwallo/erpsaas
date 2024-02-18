<?php

namespace App\Events;

use App\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlaidSuccess
{
    use Dispatchable;
    use SerializesModels;

    public string $publicToken;

    public string $accessToken;

    public Company $company;

    /**
     * Create a new event instance.
     */
    public function __construct(string $publicToken, string $accessToken, Company $company)
    {
        $this->publicToken = $publicToken;
        $this->accessToken = $accessToken;
        $this->company = $company;
    }
}
