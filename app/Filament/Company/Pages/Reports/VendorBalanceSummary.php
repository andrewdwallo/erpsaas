<?php

namespace App\Filament\Company\Pages\Reports;

use App\Enums\Accounting\DocumentEntityType;

class VendorBalanceSummary extends BaseEntityBalanceSummaryReportPage
{
    protected function getEntityType(): DocumentEntityType
    {
        return DocumentEntityType::Vendor;
    }
}
