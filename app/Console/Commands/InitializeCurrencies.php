<?php

namespace App\Console\Commands;

use Akaunting\Money\Currency;
use App\Contracts\CurrencyHandler;
use App\Facades\Forex;
use App\Models\Service\CurrencyList;
use Illuminate\Console\Command;

class InitializeCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize currencies from the API';

    public function __construct(private readonly CurrencyHandler $currencyService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Fetching supported currencies from the API...');

        $apiSupportedCurrencies = $this->currencyService->getSupportedCurrencies();

        if (Forex::isDisabled()) {
            $this->error('The Currency Exchange Rate feature is disabled.');

            return;
        }

        if (empty($apiSupportedCurrencies)) {
            $this->error('Failed to fetch supported currencies from the API.');

            return;
        }

        $appSupportedCurrencies = array_keys(Currency::getCurrencies());

        foreach ($appSupportedCurrencies as $appSupportedCurrency) {
            $isAvailable = in_array($appSupportedCurrency, $apiSupportedCurrencies, true);
            $currencyAttributes = [
                'code' => $appSupportedCurrency,
                'name' => currency($appSupportedCurrency)->getName(),
                'entity' => currency($appSupportedCurrency)->getEntity(),
                'available' => $isAvailable,
            ];

            CurrencyList::updateOrCreate(
                ['code' => $appSupportedCurrency],
                $currencyAttributes
            );
        }

        $this->info('Successfully initialized currencies.');
    }
}
