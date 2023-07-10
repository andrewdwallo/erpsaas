<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function getExchangeRate($from, $to)
    {
        $date = Carbon::today()->format('Y-m-d');

        $req_url = 'https://api.exchangerate.host/convert?from=' . $from . '&to=' . $to . '&date=' . $date;

        $response = Http::get($req_url);

        if ($response->successful()) {
            $responseData = $response->json();
            if ($responseData['success'] === true) {
                return $responseData['info']['rate'];
            }
        }

        return null;
    }

    public function getCachedExchangeRate(string $defaultCurrencyCode, string $code): ?float
    {
        // Include both the default currency code and the target currency code in the cache key
        $cacheKey = 'currency_data_' . $defaultCurrencyCode . '_' . $code;

        // Attempt to retrieve the cached exchange rate
        $cachedData = Cache::get($cacheKey);

        // If the cached exchange rate exists, return it
        if ($cachedData !== null) {
            return $cachedData['rate'];
        }

        // If the cached exchange rate does not exist, retrieve it from the API
        $rate = $this->getExchangeRate($defaultCurrencyCode, $code);

        // If the API call was successful, cache the exchange rate
        if ($rate !== null) {
            // Store the exchange rate in the cache for 24 hours
            $dataToCache = compact('rate');
            $expirationTimeInSeconds = 60 * 60 * 24; // 24 hours
            Cache::put($cacheKey, $dataToCache, $expirationTimeInSeconds);
        }

        return $rate;
    }
}
