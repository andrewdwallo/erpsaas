<?php

namespace App\Observers;

use App\Enums\Accounting\RecurringInvoiceStatus;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\RecurringInvoice;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceObserver
{
    public function saving(RecurringInvoice $recurringInvoice): void
    {
        if (
            $recurringInvoice->wasApproved() &&
            (($recurringInvoice->isDirty('start_date') && ! $recurringInvoice->last_date))
        ) {
            $recurringInvoice->next_date = $recurringInvoice->calculateNextDate();
        }

        if ($recurringInvoice->end_type?->isAfter() && $recurringInvoice->occurrences_count >= $recurringInvoice->max_occurrences) {
            $recurringInvoice->status = RecurringInvoiceStatus::Ended;
            $recurringInvoice->ended_at = now();
        }
    }

    public function saved(RecurringInvoice $recurringInvoice): void
    {
        if ($recurringInvoice->wasChanged('status')) {
            $recurringInvoice->generateDueInvoices();
        }
    }

    protected function otherScheduleDetailsChanged(RecurringInvoice $recurringInvoice): bool
    {
        return $recurringInvoice->isDirty([
            'frequency',
            'interval_type',
            'interval_value',
            'month',
            'day_of_month',
            'day_of_week',
            'end_type',
            'max_occurrences',
            'end_date',
        ]);
    }

    public function deleted(RecurringInvoice $recurringInvoice): void
    {
        DB::transaction(function () use ($recurringInvoice) {
            $recurringInvoice->lineItems()->each(function (DocumentLineItem $lineItem) {
                $lineItem->delete();
            });
        });
    }
}
