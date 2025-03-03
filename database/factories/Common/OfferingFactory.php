<?php

namespace Database\Factories\Common;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Common\OfferingType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Adjustment;
use App\Models\Common\Offering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offering>
 */
class OfferingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Offering::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'type' => $this->faker->randomElement(OfferingType::cases()),
            'price' => $this->faker->numberBetween(5, 1000),
            'sellable' => $this->faker->boolean(80),
            'purchasable' => $this->faker->boolean(80),
            'income_account_id' => function (array $attributes) {
                return $attributes['sellable'] ? 10 : null;
            },
            'expense_account_id' => function (array $attributes) {
                return $attributes['purchasable'] ? $this->faker->numberBetween(17, 35) : null;
            },
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function sellable(): self
    {
        $incomeAccount = Account::query()
            ->where('category', AccountCategory::Revenue)
            ->where('type', AccountType::OperatingRevenue)
            ->inRandomOrder()
            ->first();

        return $this->state(function (array $attributes) use ($incomeAccount) {
            return [
                'sellable' => true,
                'income_account_id' => $incomeAccount?->id ?? 10,
            ];
        });
    }

    public function purchasable(): self
    {
        $expenseAccount = Account::query()
            ->where('category', AccountCategory::Expense)
            ->where('type', AccountType::OperatingExpense)
            ->inRandomOrder()
            ->first();

        return $this->state(function (array $attributes) use ($expenseAccount) {
            return [
                'purchasable' => true,
                'expense_account_id' => $expenseAccount?->id ?? $this->faker->numberBetween(17, 35),
            ];
        });
    }

    public function withSalesAdjustments(): self
    {
        return $this->afterCreating(function (Offering $offering) {
            if ($offering->sellable) {
                $adjustments = $offering->company?->adjustments()
                    ->where('type', AdjustmentType::Sales)
                    ->pluck('id');

                $adjustmentsToAttach = $adjustments->isNotEmpty()
                    ? $adjustments->random(min(2, $adjustments->count()))
                    : Adjustment::factory()->salesTax()->count(2)->create()->pluck('id');

                $offering->salesAdjustments()->attach($adjustmentsToAttach);
            }
        });
    }

    public function withPurchaseAdjustments(): self
    {
        return $this->afterCreating(function (Offering $offering) {
            if ($offering->purchasable) {
                $adjustments = $offering->company?->adjustments()
                    ->where('type', AdjustmentType::Purchase)
                    ->pluck('id');

                $adjustmentsToAttach = $adjustments->isNotEmpty()
                    ? $adjustments->random(min(2, $adjustments->count()))
                    : Adjustment::factory()->purchaseTax()->count(2)->create()->pluck('id');

                $offering->purchaseAdjustments()->attach($adjustmentsToAttach);
            }
        });
    }
}
