<?php

namespace App\Providers;

use App\Contracts\CurrencyHandler;
use App\Services\CurrencyService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CurrencyHandler::class, function (Application $app) {
            $apiKey = config('services.currency_api.key');
            $baseUrl = config('services.currency_api.base_url');
            $client = $app->make(Client::class);

            return new CurrencyService($apiKey, $baseUrl, $client);
        });
    }

    public function boot(): void
    {
    }
}
