<?php

namespace App\Jobs;

use App\Enums\Accounting\InvoiceStatus;
use App\Models\Accounting\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOverdueInvoices implements ShouldQueue
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
        Invoice::query()
            ->whereIn('status', InvoiceStatus::canBeOverdue())
            ->where('due_date', '<', today())
            ->update(['status' => InvoiceStatus::Overdue]);
    }
}
