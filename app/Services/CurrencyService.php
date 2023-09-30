<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, Http};

class CurrencyService
{
    public function getExchangeRate($from, $to)
    {
        $api_key = config('services.currency_api.key');
        $base_url = config('services.currency_api.base_url');

        $req_url = "{$base_url}/{$api_key}/pair/{$from}/{$to}";

        $response = Http::get($req_url);

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['conversion_rate'])) {
                return $responseData['conversion_rate'];
            }
        }

        return null;
    }

    public function getCachedExchangeRate(string $defaultCurrencyCode, string $code): ?float
    {
        $cacheKey = 'currency_data_' . $defaultCurrencyCode . '_' . $code;

        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return $cachedData['rate'];
        }

        $rate = $this->getExchangeRate($defaultCurrencyCode, $code);

        if ($rate !== null) {
            $dataToCache = compact('rate');
            $expirationTimeInSeconds = 60 * 60 * 24; // 24 hours
            Cache::put($cacheKey, $dataToCache, $expirationTimeInSeconds);
        }

        return $rate;
    }
}
