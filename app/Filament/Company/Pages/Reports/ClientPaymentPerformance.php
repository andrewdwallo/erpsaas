<?php

namespace App\Filament\Company\Pages\Reports;

use App\Enums\Accounting\DocumentEntityType;

class ClientPaymentPerformance extends BaseEntityPaymentPerformanceReportPage
{
    protected function getEntityType(): DocumentEntityType
    {
        return DocumentEntityType::Client;
    }
}
