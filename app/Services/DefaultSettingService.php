<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\DefaultSetting;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use Illuminate\Support\Facades\Auth;

class DefaultSettingService
{
    public function createDefaultSettings(Company $company): void
    {
        $categories = $this->createDefaultCategories($company);
        $currency = $this->createDefaultCurrency($company);
        $salesTax = $this->createDefaultSalesTax($company);
        $purchaseTax = $this->createDefaultPurchaseTax($company);
        $salesDiscount = $this->createDefaultSalesDiscount($company);
        $purchaseDiscount = $this->createDefaultPurchaseDiscount($company);

        $defaultSettings = [
            'company_id' => $company->id,
            'income_category_id' => $categories['income_category_id'],
            'expense_category_id' => $categories['expense_category_id'],
            'currency_code' => $currency->code,
            'sales_tax_id' => $salesTax->id,
            'purchase_tax_id' => $purchaseTax->id,
            'sales_discount_id' => $salesDiscount->id,
            'purchase_discount_id' => $purchaseDiscount->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        DefaultSetting::create($defaultSettings);

        $this->createDefaultDocuments($company);
    }

    private function createDefaultCategories(Company $company): array
    {
        $incomeCategories = ['Salary', 'Bonus', 'Interest', 'Dividends', 'Rentals'];
        $expenseCategories = ['Rent', 'Utilities', 'Food', 'Transportation', 'Entertainment'];

        $shuffledCategories = [
            ...array_map(static fn ($name) => ['name' => $name, 'type' => 'income'], $incomeCategories),
            ...array_map(static fn ($name) => ['name' => $name, 'type' => 'expense'], $expenseCategories),
        ];

        shuffle($shuffledCategories);

        $incomeEnabled = $expenseEnabled = false;

        $defaultSettings = [];

        foreach ($shuffledCategories as $category) {
            $enabled = false;
            if (!$incomeEnabled && $category['type'] === 'income') {
                $enabled = $incomeEnabled = true;
            } elseif (!$expenseEnabled && $category['type'] === 'expense') {
                $enabled = $expenseEnabled = true;
            }

            $categoryModel = Category::factory()->create([
                'company_id' => $company->id,
                'name' => $category['name'],
                'type' => $category['type'],
                'enabled' => $enabled,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $defaultSettings[$category['type'] . '_category_id'] = $categoryModel->id;
        }

        return $defaultSettings;
    }

    private function createDefaultDocuments(Company $company): void
    {
        DocumentDefault::factory()->invoice()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        DocumentDefault::factory()->bill()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function createDefaultCurrency(Company $company): Currency
    {
        return Currency::factory()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function createDefaultSalesTax(Company $company): Tax
    {
        return Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function createDefaultPurchaseTax(Company $company): Tax
    {
        return Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function createDefaultSalesDiscount(Company $company): Discount
    {
        return Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function createDefaultPurchaseDiscount(Company $company): Discount
    {
        return Discount::factory()->purchaseDiscount()->create([
            'company_id' => $company->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }
}
