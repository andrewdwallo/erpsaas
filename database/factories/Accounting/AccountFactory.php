<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Setting\Currency;
use App\Utilities\Accounting\AccountCode;
use App\Utilities\Currency\CurrencyAccessor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'subtype_id' => 1,
            'name' => $this->faker->unique()->word,
            'currency_code' => CurrencyAccessor::getDefaultCurrency() ?? 'USD',
            'description' => $this->faker->sentence,
            'archived' => false,
            'default' => false,
        ];
    }

    public function withBankAccount(string $name): static
    {
        return $this->afterCreating(function (Account $account) use ($name) {
            $accountSubtype = AccountSubtype::where('name', 'Cash and Cash Equivalents')->first();

            // Create and associate a BankAccount with the Account
            $bankAccount = BankAccount::factory()->create([
                'account_id' => $account->id, // Associate with Account
            ]);

            // Update the Account with the subtype and name
            $account->update([
                'subtype_id' => $accountSubtype->id,
                'name' => $name,
            ]);
        });
    }

    public function withForeignBankAccount(string $name, string $currencyCode, float $rate): static
    {
        return $this->afterCreating(function (Account $account) use ($currencyCode, $rate, $name) {
            $accountSubtype = AccountSubtype::where('name', 'Cash and Cash Equivalents')->first();

            // Create the Currency and BankAccount
            $currency = Currency::factory()->forCurrency($currencyCode, $rate)->create();
            $bankAccount = BankAccount::factory()->create([
                'account_id' => $account->id, // Associate with Account
            ]);

            // Update the Account with the subtype, name, and currency code
            $account->update([
                'subtype_id' => $accountSubtype->id,
                'name' => $name,
                'currency_code' => $currency->code,
            ]);
        });
    }

    public function forSalesTax(?string $name = null, ?string $description = null): static
    {
        $accountSubtype = AccountSubtype::where('name', 'Sales Taxes')->first();

        return $this->state([
            'name' => $name,
            'description' => $description,
            'category' => $accountSubtype->category,
            'type' => $accountSubtype->type,
            'subtype_id' => $accountSubtype->id,
            'code' => AccountCode::generate($accountSubtype),
        ]);
    }

    public function forPurchaseTax(?string $name = null, ?string $description = null): static
    {
        $accountSubtype = AccountSubtype::where('name', 'Input Tax Recoverable')->first();

        return $this->state([
            'name' => $name,
            'description' => $description,
            'category' => $accountSubtype->category,
            'type' => $accountSubtype->type,
            'subtype_id' => $accountSubtype->id,
            'code' => AccountCode::generate($accountSubtype),
        ]);
    }

    public function forSalesDiscount(?string $name = null, ?string $description = null): static
    {
        $accountSubtype = AccountSubtype::where('name', 'Sales Discounts')->first();

        return $this->state([
            'name' => $name,
            'description' => $description,
            'category' => $accountSubtype->category,
            'type' => $accountSubtype->type,
            'subtype_id' => $accountSubtype->id,
            'code' => AccountCode::generate($accountSubtype),
        ]);
    }

    public function forPurchaseDiscount(?string $name = null, ?string $description = null): static
    {
        $accountSubtype = AccountSubtype::where('name', 'Purchase Discounts')->first();

        return $this->state([
            'name' => $name,
            'description' => $description,
            'category' => $accountSubtype->category,
            'type' => $accountSubtype->type,
            'subtype_id' => $accountSubtype->id,
            'code' => AccountCode::generate($accountSubtype),
        ]);
    }
}
