<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasLabel;

enum DocumentEntityType: string implements HasLabel
{
    case Client = 'client';
    case Vendor = 'vendor';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getAgingReportTitle(): string
    {
        return match ($this) {
            self::Client => 'Accounts Receivable Aging',
            self::Vendor => 'Accounts Payable Aging',
        };
    }

    public function getBalanceSummaryReportTitle(): string
    {
        return match ($this) {
            self::Client => 'Client Balance Summary',
            self::Vendor => 'Vendor Balance Summary',
        };
    }

    public function getPaymentPerformanceReportTitle(): string
    {
        return match ($this) {
            self::Client => 'Client Payment Performance',
            self::Vendor => 'Vendor Payment Performance',
        };
    }
}
