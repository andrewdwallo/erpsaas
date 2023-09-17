<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Company;
use App\Models\Setting\Appearance;
use App\Models\Setting\Category;
use App\Models\Setting\CompanyDefault;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyDefaultService
{
    public function createCompanyDefaults(Company $company, User $user): void
    {
        DB::transaction(function () use ($company, $user) {
            $categories = $this->createCategories($company, $user);
            $currency = $this->createCurrency($company, $user);
            $salesTax = $this->createSalesTax($company, $user);
            $purchaseTax = $this->createPurchaseTax($company, $user);
            $salesDiscount = $this->createSalesDiscount($company, $user);
            $purchaseDiscount = $this->createPurchaseDiscount($company, $user);
            $this->createAppearance($company, $user);
            $this->createDocumentDefaults($company, $user);

            $companyDefaults = [
                'company_id' => $company->id,
                'income_category_id' => $categories['income_category_id'],
                'expense_category_id' => $categories['expense_category_id'],
                'currency_code' => $currency->code,
                'sales_tax_id' => $salesTax->id,
                'purchase_tax_id' => $purchaseTax->id,
                'sales_discount_id' => $salesDiscount->id,
                'purchase_discount_id' => $purchaseDiscount->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            CompanyDefault::updateOrInsert(['company_id' => $company->id], $companyDefaults);
        }, 5);
    }

    private function createCategories(Company $company, User $user): array
    {
        $incomeCategories = ['Salary', 'Bonus', 'Interest', 'Dividends', 'Rentals'];
        $expenseCategories = ['Rent', 'Utilities', 'Food', 'Transportation', 'Entertainment'];

        $shuffledCategories = [
            ...array_map(static fn ($name) => ['name' => $name, 'type' => CategoryType::Income->value], $incomeCategories),
            ...array_map(static fn ($name) => ['name' => $name, 'type' => CategoryType::Expense->value], $expenseCategories),
        ];

        shuffle($shuffledCategories);

        $incomeEnabled = $expenseEnabled = false;

        $enabledIncomeCategoryId = null;
        $enabledExpenseCategoryId = null;

        foreach ($shuffledCategories as $category) {
            $enabled = false;
            if (!$incomeEnabled && $category['type'] === CategoryType::Income->value) {
                $enabled = $incomeEnabled = true;
            } elseif (!$expenseEnabled && $category['type'] === CategoryType::Expense->value) {
                $enabled = $expenseEnabled = true;
            }

            $categoryModel = Category::factory()->create([
                'company_id' => $company->id,
                'name' => $category['name'],
                'type' => $category['type'],
                'enabled' => $enabled,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            if ($enabled && $category['type'] === CategoryType::Income->value) {
                $enabledIncomeCategoryId = $categoryModel->id;
            } elseif ($enabled && $category['type'] === CategoryType::Expense->value) {
                $enabledExpenseCategoryId = $categoryModel->id;
            }
        }

        return [
            'income_category_id' => $enabledIncomeCategoryId,
            'expense_category_id' => $enabledExpenseCategoryId,
        ];
    }

    private function createCurrency(Company $company, User $user)
    {
        return Currency::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesTax(Company $company, User $user)
    {
        return Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseTax(Company $company, User $user)
    {
        return Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesDiscount(Company $company, User $user)
    {
        return Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseDiscount(Company $company, User $user)
    {
        return Discount::factory()->purchaseDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createAppearance(Company $company, User $user): void
    {
        Appearance::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createDocumentDefaults(Company $company, User $user): void
    {
        DocumentDefault::factory()->invoice()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        DocumentDefault::factory()->bill()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

}
