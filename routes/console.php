<?php

use App\Console\Commands\TriggerRecurringInvoiceGeneration;
use App\Console\Commands\UpdateOverdueInvoices;
use Illuminate\Support\Facades\Schedule;

Schedule::command(UpdateOverdueInvoices::class)->everyFiveMinutes();
Schedule::command(TriggerRecurringInvoiceGeneration::class, ['--queue'])->everyMinute();
