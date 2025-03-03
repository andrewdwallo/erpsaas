<?php

namespace App\Console\Commands;

use App\Models\Banking\ConnectedBankAccount;
use App\Services\PlaidService;
use Illuminate\Console\Command;

class FirePlaidWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plaid:fire-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(PlaidService $plaidService): void
    {
        $accessToken = ConnectedBankAccount::first()->access_token;
        $webhookCode = 'DEFAULT_UPDATE';
        $webhookType = 'TRANSACTIONS';

        try {
            $response = $plaidService->fireSandboxWebhook($accessToken, $webhookCode, $webhookType);
            $this->info('Webhook Fired Successfully' . PHP_EOL . json_encode($response, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->error('Failed to Fire Webhook' . PHP_EOL . $e->getMessage());
        }
    }
}
