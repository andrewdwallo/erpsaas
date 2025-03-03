<?php

namespace App\Concerns;

use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Models\Accounting\Bill;
use App\Models\Accounting\DocumentLineItem;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\Utilities\RateCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait ManagesLineItems
{
    protected function handleLineItems(Model $record, Collection $lineItems): void
    {
        foreach ($lineItems as $itemData) {
            $lineItem = isset($itemData['id'])
                ? $record->lineItems->find($itemData['id'])
                : $record->lineItems()->make();

            $lineItem->fill([
                'offering_id' => $itemData['offering_id'],
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
            ]);

            if (! $lineItem->exists) {
                $lineItem->documentable()->associate($record);
            }

            $lineItem->save();

            $this->handleLineItemAdjustments($lineItem, $itemData, $record->discount_method);
            $this->updateLineItemTotals($lineItem, $record->discount_method);
        }
    }

    protected function deleteRemovedLineItems(Model $record, Collection $lineItems): void
    {
        $existingLineItemIds = $record->lineItems->pluck('id');
        $updatedLineItemIds = $lineItems->pluck('id')->filter();
        $lineItemsToDelete = $existingLineItemIds->diff($updatedLineItemIds);

        if ($lineItemsToDelete->isNotEmpty()) {
            $record
                ->lineItems()
                ->whereIn('id', $lineItemsToDelete)
                ->each(fn (DocumentLineItem $lineItem) => $lineItem->delete());
        }
    }

    protected function handleLineItemAdjustments(DocumentLineItem $lineItem, array $itemData, DocumentDiscountMethod $discountMethod): void
    {
        $isBill = $lineItem->documentable instanceof Bill;

        $taxType = $isBill ? 'purchaseTaxes' : 'salesTaxes';
        $discountType = $isBill ? 'purchaseDiscounts' : 'salesDiscounts';

        $adjustmentIds = collect($itemData[$taxType] ?? [])
            ->merge($discountMethod->isPerLineItem() ? ($itemData[$discountType] ?? []) : [])
            ->filter()
            ->unique();

        $lineItem->adjustments()->sync($adjustmentIds);
        $lineItem->refresh();
    }

    protected function updateLineItemTotals(DocumentLineItem $lineItem, DocumentDiscountMethod $discountMethod): void
    {
        $lineItem->updateQuietly([
            'tax_total' => $lineItem->calculateTaxTotal()->getAmount(),
            'discount_total' => $discountMethod->isPerLineItem()
                ? $lineItem->calculateDiscountTotal()->getAmount()
                : 0,
        ]);
    }

    protected function updateDocumentTotals(Model $record, array $data): array
    {
        $currencyCode = $data['currency_code'] ?? $record->currency_code ?? CurrencyAccessor::getDefaultCurrency();
        $subtotalCents = $record->lineItems()->sum('subtotal');
        $taxTotalCents = $record->lineItems()->sum('tax_total');
        $discountTotalCents = $this->calculateDiscountTotal(
            DocumentDiscountMethod::parse($data['discount_method']),
            AdjustmentComputation::parse($data['discount_computation']),
            $data['discount_rate'] ?? null,
            $subtotalCents,
            $record,
            $currencyCode,
        );

        $grandTotalCents = $subtotalCents + $taxTotalCents - $discountTotalCents;

        return [
            'subtotal' => CurrencyConverter::convertCentsToFormatSimple($subtotalCents, $currencyCode),
            'tax_total' => CurrencyConverter::convertCentsToFormatSimple($taxTotalCents, $currencyCode),
            'discount_total' => CurrencyConverter::convertCentsToFormatSimple($discountTotalCents, $currencyCode),
            'total' => CurrencyConverter::convertCentsToFormatSimple($grandTotalCents, $currencyCode),
        ];
    }

    protected function calculateDiscountTotal(
        DocumentDiscountMethod $discountMethod,
        ?AdjustmentComputation $discountComputation,
        ?string $discountRate,
        int $subtotalCents,
        Model $record,
        string $currencyCode
    ): int {
        if ($discountMethod->isPerLineItem()) {
            return $record->lineItems()->sum('discount_total');
        }

        if ($discountComputation?->isPercentage()) {
            $scaledRate = RateCalculator::parseLocalizedRate($discountRate);

            return RateCalculator::calculatePercentage($subtotalCents, $scaledRate);
        }

        return CurrencyConverter::convertToCents($discountRate, $currencyCode);
    }
}
