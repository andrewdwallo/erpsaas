<?php

namespace App\Listeners;

use App\Enums\ContactType;
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
        $company = $event->company;
        $employee = $event->user;

        $company->contacts()->create([
            'type' => ContactType::Employee,
            'name' => $employee->name,
            'email' => $employee->email,
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }
}
