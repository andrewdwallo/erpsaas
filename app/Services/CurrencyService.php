<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Http};

class CurrencyService
{
    public function getExchangeRates($base)
    {
        $api_key = config('services.currency_api.key');
        $base_url = config('services.currency_api.base_url');

        $req_url = "{$base_url}/{$api_key}/latest/{$base}";

        $response = Http::get($req_url);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['conversion_rates'])) {
                return $responseData['conversion_rates'];
            }
        }

        return null;
    }

    public function updateCachedExchangeRates(string $base): void
    {
        $rates = $this->getExchangeRates($base);

        if ($rates !== null) {
            $expirationTimeInSeconds = 60 * 60 * 24; // 1 day (24 hours)

            foreach ($rates as $code => $rate) {
                $cacheKey = 'currency_data_' . $base . '_' . $code;
                Cache::put($cacheKey, $rate, $expirationTimeInSeconds);
            }
        }
    }

    public function getCachedExchangeRate(string $defaultCurrencyCode, string $code): ?float
    {
        $cacheKey = 'currency_data_' . $defaultCurrencyCode . '_' . $code;

        $cachedRate = Cache::get($cacheKey);

        if ($cachedRate === null) {
            $this->updateCachedExchangeRates($defaultCurrencyCode);
            $cachedRate = Cache::get($cacheKey);
        }

        return $cachedRate;
    }
}
