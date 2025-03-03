<?php

namespace Database\Factories\Accounting;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\AdjustmentScope;
use App\Enums\Accounting\AdjustmentType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Adjustment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Adjustment>
 */
class AdjustmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Adjustment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 year');
        $endDate = $this->faker->dateTimeBetween($startDate, Carbon::parse($startDate)->addYear());

        /** @var AdjustmentComputation $computation */
        $computation = $this->faker->randomElement(AdjustmentComputation::class);

        $rate = $computation->isFixed()
            ? $this->faker->numberBetween(5, 100) * 100 // $5 - $100 for fixed amounts
            : $this->faker->numberBetween(3, 25) * 10000; // 3% - 25% for percentages

        return [
            'rate' => $rate,
            'computation' => $computation,
            'category' => $this->faker->randomElement(AdjustmentCategory::class),
            'type' => $this->faker->randomElement(AdjustmentType::class),
            'scope' => $this->faker->randomElement(AdjustmentScope::class),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Adjustment $adjustment) {
            if ($adjustment->account_id === null) {
                $account = Account::factory()->create();
                $adjustment->account()->associate($account);
            }
        });
    }

    /**
     * Define a sales tax adjustment.
     */
    public function salesTax(?string $name = null, ?string $description = null): self
    {
        $name = $name ?? 'Sales Tax';
        $account = Account::factory()->forSalesTax($name, $description)->create();

        return $this->state([
            'category' => AdjustmentCategory::Tax,
            'type' => AdjustmentType::Sales,
            'account_id' => $account->id,
        ]);
    }

    /**
     * Define a purchase tax adjustment.
     */
    public function purchaseTax(?string $name = null, ?string $description = null): self
    {
        $name = $name ?? 'Purchase Tax';
        $account = Account::factory()->forPurchaseTax($name, $description)->create();

        return $this->state([
            'category' => AdjustmentCategory::Tax,
            'type' => AdjustmentType::Purchase,
            'account_id' => $account->id,
        ]);
    }

    /**
     * Define a sales discount adjustment.
     */
    public function salesDiscount(?string $name = null, ?string $description = null): self
    {
        $name = $name ?? 'Sales Discount';
        $account = Account::factory()->forSalesDiscount($name, $description)->create();

        return $this->state([
            'category' => AdjustmentCategory::Discount,
            'type' => AdjustmentType::Sales,
            'account_id' => $account->id,
        ]);
    }

    /**
     * Define a purchase discount adjustment.
     */
    public function purchaseDiscount(?string $name = null, ?string $description = null): self
    {
        $name = $name ?? 'Purchase Discount';
        $account = Account::factory()->forPurchaseDiscount($name, $description)->create();

        return $this->state([
            'category' => AdjustmentCategory::Discount,
            'type' => AdjustmentType::Purchase,
            'account_id' => $account->id,
        ]);
    }
}
