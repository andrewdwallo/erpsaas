<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use Database\Factories\CategoryFactory;
use Illuminate\Support\Carbon;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        DocumentDefault::factory()->invoice()->create([
            'company_id' => $company->id,
        ]);

        DocumentDefault::factory()->bill()->create([
            'company_id' => $company->id,
        ]);

        Currency::factory()->create([
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);

        Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);

        Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);

        Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);

        Discount::factory()->purchaseDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $company->user_id,
        ]);
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
