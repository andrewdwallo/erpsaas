<?php

namespace App\Events;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyGenerated
{
    use Dispatchable;
    use SerializesModels;

    public User $user;

    public Company $company;

    public string $country;

    public string $language;

    public string $currency;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Company $company, string $country, string $language = 'en', string $currency = 'USD')
    {
        $this->user = $user;
        $this->company = $company;
        $this->country = $country;
        $this->language = $language;
        $this->currency = $currency;
    }
}
