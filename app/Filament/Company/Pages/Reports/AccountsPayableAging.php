<?php

namespace App\Filament\Company\Pages\Reports;

use App\Enums\Accounting\DocumentEntityType;

class AccountsPayableAging extends BaseAgingReportPage
{
    protected function getEntityType(): DocumentEntityType
    {
        return DocumentEntityType::Vendor;
    }
}
