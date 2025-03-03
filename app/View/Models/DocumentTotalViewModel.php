<?php

namespace App\View\Models;

use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Models\Accounting\Adjustment;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\Utilities\RateCalculator;
use Illuminate\Support\Number;

class DocumentTotalViewModel
{
    public function __construct(
        public ?array $data,
        public DocumentType $documentType = DocumentType::Invoice,
    ) {}

    public function buildViewData(): array
    {
        $currencyCode = $this->data['currency_code'] ?? CurrencyAccessor::getDefaultCurrency();
        $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();

        $lineItems = collect($this->data['lineItems'] ?? []);

        $subtotalInCents = $lineItems->sum(fn ($item) => $this->calculateLineSubtotalInCents($item, $currencyCode));

        $taxTotalInCents = $this->calculateAdjustmentsTotalInCents($lineItems, $this->documentType->getTaxKey(), $currencyCode);
        $discountTotalInCents = $this->calculateDiscountTotalInCents($lineItems, $subtotalInCents, $currencyCode);

        $grandTotalInCents = $subtotalInCents + ($taxTotalInCents - $discountTotalInCents);

        $conversionMessage = $this->buildConversionMessage($grandTotalInCents, $currencyCode, $defaultCurrencyCode);

        return [
            'subtotal' => CurrencyConverter::formatCentsToMoney($subtotalInCents, $currencyCode),
            'taxTotal' => CurrencyConverter::formatCentsToMoney($taxTotalInCents, $currencyCode),
            'discountTotal' => CurrencyConverter::formatCentsToMoney($discountTotalInCents, $currencyCode),
            'grandTotal' => CurrencyConverter::formatCentsToMoney($grandTotalInCents, $currencyCode),
            'conversionMessage' => $conversionMessage,
        ];
    }

    private function calculateLineSubtotalInCents(array $item, string $currencyCode): int
    {
        $quantity = max((float) ($item['quantity'] ?? 0), 0);
        $unitPrice = max((float) ($item['unit_price'] ?? 0), 0);

        $subtotal = $quantity * $unitPrice;

        return CurrencyConverter::convertToCents($subtotal, $currencyCode);
    }

    private function calculateAdjustmentsTotalInCents($lineItems, string $key, string $currencyCode): int
    {
        return $lineItems->reduce(function ($carry, $item) use ($key, $currencyCode) {
            $quantity = max((float) ($item['quantity'] ?? 0), 0);
            $unitPrice = max((float) ($item['unit_price'] ?? 0), 0);
            $adjustmentIds = $item[$key] ?? [];
            $lineTotal = $quantity * $unitPrice;

            $lineTotalInCents = CurrencyConverter::convertToCents($lineTotal, $currencyCode);

            $adjustmentTotal = Adjustment::whereIn('id', $adjustmentIds)
                ->get()
                ->sum(function (Adjustment $adjustment) use ($lineTotalInCents) {
                    if ($adjustment->computation->isPercentage()) {
                        return RateCalculator::calculatePercentage($lineTotalInCents, $adjustment->getRawOriginal('rate'));
                    } else {
                        return $adjustment->getRawOriginal('rate');
                    }
                });

            return $carry + $adjustmentTotal;
        }, 0);
    }

    private function calculateDiscountTotalInCents($lineItems, int $subtotalInCents, string $currencyCode): int
    {
        $discountMethod = DocumentDiscountMethod::parse($this->data['discount_method']) ?? DocumentDiscountMethod::PerLineItem;

        if ($discountMethod->isPerLineItem()) {
            return $this->calculateAdjustmentsTotalInCents($lineItems, $this->documentType->getDiscountKey(), $currencyCode);
        }

        $discountComputation = AdjustmentComputation::parse($this->data['discount_computation']) ?? AdjustmentComputation::Percentage;
        $discountRate = blank($this->data['discount_rate']) ? '0' : $this->data['discount_rate'];

        if ($discountComputation->isPercentage()) {
            $scaledDiscountRate = RateCalculator::parseLocalizedRate($discountRate);

            return RateCalculator::calculatePercentage($subtotalInCents, $scaledDiscountRate);
        }

        if (! CurrencyConverter::isValidAmount($discountRate)) {
            $discountRate = '0';
        }

        return CurrencyConverter::convertToCents($discountRate, $currencyCode);
    }

    private function buildConversionMessage(int $grandTotalInCents, string $currencyCode, string $defaultCurrencyCode): ?string
    {
        if ($currencyCode === $defaultCurrencyCode) {
            return null;
        }

        $rate = currency($currencyCode)->getRate();
        $indirectRate = 1 / $rate;

        $convertedTotalInCents = CurrencyConverter::convertBalance($grandTotalInCents, $currencyCode, $defaultCurrencyCode);

        $formattedRate = Number::format($indirectRate, maxPrecision: 10);

        return sprintf(
            'Currency conversion: %s (%s) at an exchange rate of 1 %s = %s %s',
            CurrencyConverter::formatCentsToMoney($convertedTotalInCents, $defaultCurrencyCode),
            $defaultCurrencyCode,
            $currencyCode,
            $formattedRate,
            $defaultCurrencyCode
        );
    }
}
