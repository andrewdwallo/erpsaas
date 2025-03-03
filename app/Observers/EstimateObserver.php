<?php

namespace App\Observers;

use App\Enums\Accounting\EstimateStatus;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Estimate;
use Illuminate\Support\Facades\DB;

class EstimateObserver
{
    public function saving(Estimate $estimate): void
    {
        if ($estimate->approved_at && $estimate->is_currently_expired) {
            $estimate->status = EstimateStatus::Expired;
        }
    }

    public function deleted(Estimate $estimate): void
    {
        DB::transaction(function () use ($estimate) {
            $estimate->lineItems()->each(function (DocumentLineItem $lineItem) {
                $lineItem->delete();
            });
        });
    }
}
