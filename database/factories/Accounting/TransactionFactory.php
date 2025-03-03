<?php

namespace Database\Factories\Accounting;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Models\Setting\CompanyDefault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'bank_account_id' => 1,
            'account_id' => $this->faker->numberBetween(2, 30),
            'type' => $this->faker->randomElement([TransactionType::Deposit, TransactionType::Withdrawal]),
            'description' => $this->faker->sentence,
            'notes' => $this->faker->paragraph,
            'amount' => $this->faker->numberBetween(100, 5000),
            'reviewed' => $this->faker->boolean,
            'posted_at' => $this->faker->dateTimeBetween('-2 years'),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function forCompanyAndBankAccount(Company $company, BankAccount $bankAccount): static
    {
        return $this->state(function (array $attributes) use ($bankAccount, $company) {
            $type = $this->faker->randomElement([TransactionType::Deposit, TransactionType::Withdrawal]);

            $associatedAccountTypes = match ($type) {
                TransactionType::Deposit => [
                    AccountType::CurrentLiability,
                    AccountType::NonCurrentLiability,
                    AccountType::Equity,
                    AccountType::OperatingRevenue,
                    AccountType::NonOperatingRevenue,
                    AccountType::ContraExpense,
                ],
                TransactionType::Withdrawal => [
                    AccountType::OperatingExpense,
                    AccountType::NonOperatingExpense,
                    AccountType::CurrentLiability,
                    AccountType::NonCurrentLiability,
                    AccountType::Equity,
                    AccountType::ContraRevenue,
                ],
            };

            $accountIdForBankAccount = $bankAccount->account->id;

            $excludedSubtypes = AccountSubtype::where('company_id', $company->id)
                ->whereIn('name', ['Sales Taxes', 'Purchase Taxes', 'Sales Discounts', 'Purchase Discounts'])
                ->pluck('id');

            $account = Account::whereIn('type', $associatedAccountTypes)
                ->where('company_id', $company->id)
                ->whereNotIn('subtype_id', $excludedSubtypes)
                ->whereKeyNot($accountIdForBankAccount)
                ->inRandomOrder()
                ->first();

            if (! $account) {
                $account = Account::where('company_id', $company->id)
                    ->whereKeyNot($accountIdForBankAccount)
                    ->inRandomOrder()
                    ->firstOrFail();
            }

            return [
                'company_id' => $company->id,
                'bank_account_id' => $bankAccount->id,
                'account_id' => $account->id,
                'type' => $type,
            ];
        });
    }

    public function forDefaultBankAccount(): static
    {
        return $this->state(function (array $attributes) {
            $defaultBankAccount = CompanyDefault::first()->bankAccount;

            return [
                'bank_account_id' => $defaultBankAccount->id,
            ];
        });
    }

    public function forBankAccount(?BankAccount $bankAccount = null): static
    {
        return $this->state(function (array $attributes) use ($bankAccount) {
            $bankAccount = $bankAccount ?? BankAccount::factory()->create();

            return [
                'bank_account_id' => $bankAccount->id,
            ];
        });
    }

    public function forDestinationBankAccount(?Account $account = null): static
    {
        return $this->state(function (array $attributes) use ($account) {
            $destinationBankAccount = $account ?? Account::factory()->withBankAccount('Destination Bank Account')->create();

            return [
                'account_id' => $destinationBankAccount->id,
            ];
        });
    }

    public function forUncategorizedRevenue(): static
    {
        return $this->state(function (array $attributes) {
            $account = Account::where('type', AccountType::UncategorizedRevenue)->firstOrFail();

            return [
                'account_id' => $account->id,
            ];
        });
    }

    public function forUncategorizedExpense(): static
    {
        return $this->state(function (array $attributes) {
            $account = Account::where('type', AccountType::UncategorizedExpense)->firstOrFail();

            return [
                'account_id' => $account->id,
            ];
        });
    }

    public function forAccount(Account $account): static
    {
        return $this->state([
            'account_id' => $account->id,
        ]);
    }

    public function forType(TransactionType $type, int $amount): static
    {
        return $this->state(compact('type', 'amount'));
    }

    public function asDeposit(int $amount): static
    {
        return $this->state(function () use ($amount) {
            return [
                'type' => TransactionType::Deposit,
                'amount' => $amount,
            ];
        });
    }

    public function asWithdrawal(int $amount): static
    {
        return $this->state(function () use ($amount) {
            return [
                'type' => TransactionType::Withdrawal,
                'amount' => $amount,
            ];
        });
    }

    public function asJournal(int $amount): static
    {
        return $this->state(function () use ($amount) {
            return [
                'type' => TransactionType::Journal,
                'amount' => $amount,
            ];
        });
    }

    public function asTransfer(int $amount): static
    {
        return $this->state(function () use ($amount) {
            return [
                'type' => TransactionType::Transfer,
                'amount' => $amount,
            ];
        });
    }
}
