<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\Bill;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Estimate;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Common\Offering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentLineItem>
 */
class DocumentLineItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = DocumentLineItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);

        return [
            'company_id' => 1,
            'description' => $this->faker->sentence,
            'quantity' => $quantity,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function forInvoice(Invoice | RecurringInvoice $invoice): static
    {
        return $this
            ->for($invoice, 'documentable')
            ->state(function (array $attributes) {
                $offering = Offering::where('sellable', true)
                    ->inRandomOrder()
                    ->first();

                return [
                    'offering_id' => $offering->id,
                    'unit_price' => $offering->price,
                ];
            })
            ->afterCreating(function (DocumentLineItem $lineItem) {
                $offering = $lineItem->offering;

                if ($offering) {
                    $lineItem->salesTaxes()->syncWithoutDetaching($offering->salesTaxes->pluck('id')->toArray());
                    $lineItem->salesDiscounts()->syncWithoutDetaching($offering->salesDiscounts->pluck('id')->toArray());
                }

                $lineItem->refresh();

                $taxTotal = $lineItem->calculateTaxTotal()->getAmount();
                $discountTotal = $lineItem->calculateDiscountTotal()->getAmount();

                $lineItem->updateQuietly([
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                ]);
            });
    }

    public function forEstimate(Estimate $estimate): static
    {
        return $this
            ->for($estimate, 'documentable')
            ->state(function (array $attributes) {
                $offering = Offering::where('sellable', true)
                    ->inRandomOrder()
                    ->first();

                return [
                    'offering_id' => $offering->id,
                    'unit_price' => $offering->price,
                ];
            })
            ->afterCreating(function (DocumentLineItem $lineItem) {
                $offering = $lineItem->offering;

                if ($offering) {
                    $lineItem->salesTaxes()->syncWithoutDetaching($offering->salesTaxes->pluck('id')->toArray());
                    $lineItem->salesDiscounts()->syncWithoutDetaching($offering->salesDiscounts->pluck('id')->toArray());
                }

                $lineItem->refresh();

                $taxTotal = $lineItem->calculateTaxTotal()->getAmount();
                $discountTotal = $lineItem->calculateDiscountTotal()->getAmount();

                $lineItem->updateQuietly([
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                ]);
            });
    }

    public function forBill(Bill $bill): static
    {
        return $this
            ->for($bill, 'documentable')
            ->state(function (array $attributes) {
                $offering = Offering::where('purchasable', true)
                    ->inRandomOrder()
                    ->first();

                return [
                    'offering_id' => $offering->id,
                    'unit_price' => $offering->price,
                ];
            })
            ->afterCreating(function (DocumentLineItem $lineItem) {
                $offering = $lineItem->offering;

                if ($offering) {
                    $lineItem->purchaseTaxes()->syncWithoutDetaching($offering->purchaseTaxes->pluck('id')->toArray());
                    $lineItem->purchaseDiscounts()->syncWithoutDetaching($offering->purchaseDiscounts->pluck('id')->toArray());
                }

                $lineItem->refresh();

                $taxTotal = $lineItem->calculateTaxTotal()->getAmount();
                $discountTotal = $lineItem->calculateDiscountTotal()->getAmount();

                $lineItem->updateQuietly([
                    'tax_total' => $taxTotal,
                    'discount_total' => $discountTotal,
                ]);
            });
    }
}
