<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        $incomeCategories = ['Salary', 'Bonus', 'Interest', 'Dividends', 'Rentals'];
        $expenseCategories = ['Rent', 'Utilities', 'Food', 'Transportation', 'Entertainment'];

        $shuffledCategories = [
            ...array_map(static fn ($name) => ['name' => $name, 'type' => 'income'], $incomeCategories),
            ...array_map(static fn ($name) => ['name' => $name, 'type' => 'expense'], $expenseCategories),
        ];

        shuffle($shuffledCategories);

        $incomeEnabled = $expenseEnabled = false;

        foreach ($shuffledCategories as $category) {
            $enabled = false;
            if (!$incomeEnabled && $category['type'] === 'income') {
                $enabled = $incomeEnabled = true;
            } elseif (!$expenseEnabled && $category['type'] === 'expense') {
                $enabled = $expenseEnabled = true;
            }

            Category::factory()->create([
                'company_id' => $company->id,
                'name' => $category['name'],
                'type' => $category['type'],
                'enabled' => $enabled,
                'created_by' => $company->user_id,
            ]);
        }


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
