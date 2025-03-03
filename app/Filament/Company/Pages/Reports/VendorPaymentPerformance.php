<?php

namespace App\Filament\Company\Pages\Reports;

use App\Enums\Accounting\DocumentEntityType;

class VendorPaymentPerformance extends BaseEntityPaymentPerformanceReportPage
{
    protected function getEntityType(): DocumentEntityType
    {
        return DocumentEntityType::Vendor;
    }
}
