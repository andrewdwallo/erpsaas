<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringInvoices;
use Illuminate\Console\Command;

class TriggerRecurringInvoiceGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-recurring {--queue : Whether the job should be queued}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for active recurring schedules';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if ($this->option('queue')) {
            GenerateRecurringInvoices::dispatch();

            $this->info('Recurring invoice generation has been queued.');
        } else {
            GenerateRecurringInvoices::dispatchSync();

            $this->info('Recurring invoices have been generated.');
        }
    }
}
