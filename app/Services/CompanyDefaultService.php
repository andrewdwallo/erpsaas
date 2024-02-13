<?php

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Company;
use App\Models\Setting\Appearance;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Localization;
use App\Models\Setting\Tax;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyDefaultService
{
    public function createCompanyDefaults(Company $company, User $user, string $currencyCode, string $countryCode, string $language): void
    {
        DB::transaction(function () use ($company, $user, $currencyCode, $countryCode, $language) {
            $this->createCategories($company, $user);
            $this->createCurrency($company, $user, $currencyCode);
            $this->createSalesTax($company, $user);
            $this->createPurchaseTax($company, $user);
            $this->createSalesDiscount($company, $user);
            $this->createPurchaseDiscount($company, $user);
            $this->createAppearance($company, $user);
            $this->createDocumentDefaults($company, $user);
            $this->createLocalization($company, $user, $countryCode, $language);
        }, 5);
    }

    private function createCategories(Company $company, User $user): void
    {
        $incomeCategories = ['Dividends', 'Interest Earned', 'Wages', 'Sales', 'Other Income'];
        $expenseCategories = ['Rent or Mortgage', 'Utilities', 'Groceries', 'Transportation', 'Other Expense'];
        $otherCategories = ['Transfer', 'Other'];

        $defaultIncomeCategory = 'Sales';
        $defaultExpenseCategory = 'Rent or Mortgage';

        $this->createCategory($company, $user, $defaultIncomeCategory, CategoryType::Income, true);
        $this->createCategory($company, $user, $defaultExpenseCategory, CategoryType::Expense, true);

        foreach ($incomeCategories as $incomeCategory) {
            if ($incomeCategory !== $defaultIncomeCategory) {
                $this->createCategory($company, $user, $incomeCategory, CategoryType::Income);
            }
        }

        foreach ($expenseCategories as $expenseCategory) {
            if ($expenseCategory !== $defaultExpenseCategory) {
                $this->createCategory($company, $user, $expenseCategory, CategoryType::Expense);
            }
        }

        foreach ($otherCategories as $otherCategory) {
            $this->createCategory($company, $user, $otherCategory, CategoryType::Other);
        }

    }

    private function createCategory(Company $company, User $user, string $name, CategoryType $type, bool $enabled = false): void
    {
        Category::factory()->create([
            'company_id' => $company->id,
            'name' => $name,
            'type' => $type,
            'enabled' => $enabled,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createCurrency(Company $company, User $user, string $currencyCode): void
    {
        Currency::factory()->forCurrency($currencyCode)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesTax(Company $company, User $user): void
    {
        Tax::factory()->salesTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseTax(Company $company, User $user): void
    {
        Tax::factory()->purchaseTax()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createSalesDiscount(Company $company, User $user): void
    {
        Discount::factory()->salesDiscount()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createPurchaseDiscount(Company $company, User $user): void
    {
        Discount::factory()->purchaseDiscount()->create([
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

    private function createLocalization(Company $company, User $user, string $countryCode, string $language): void
    {
        Localization::factory()->withCountry($countryCode, $language)->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
