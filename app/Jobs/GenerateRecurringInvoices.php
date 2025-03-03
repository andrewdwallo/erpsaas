<?php

namespace App\Jobs;

use App\Enums\Accounting\RecurringInvoiceStatus;
use App\Models\Accounting\RecurringInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRecurringInvoices implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        RecurringInvoice::query()
            ->where('status', RecurringInvoiceStatus::Active)
            ->chunk(100, function ($recurringInvoices) {
                foreach ($recurringInvoices as $recurringInvoice) {
                    $recurringInvoice->generateDueInvoices();
                }
            });
    }
}
