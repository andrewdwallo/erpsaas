<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the log file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $this->info('Log file cleared successfully.');
    }
}
