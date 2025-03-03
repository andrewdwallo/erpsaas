<?php

namespace App\Listeners;

use App\Enums\Common\ContactType;
use App\Models\Company;
use App\Models\User;
use Wallo\FilamentCompanies\Events\CompanyEmployeeAdded;

class CreateEmployeeContact
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyEmployeeAdded $event): void
    {
        /** @var Company $company */
        $company = $event->company;

        /** @var User $employee */
        $employee = $event->user;

        $nameParts = explode(' ', $employee->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $employee->contacts()->create([
            'company_id' => $company->id,
            'type' => ContactType::Employee,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $employee->email,
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }
}
