<?php

namespace App\Services;

use App\Contracts\CurrencyHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService implements CurrencyHandler
{
    protected ?string $api_key;

    protected ?string $base_url;

    protected Client $client;

    public function __construct(?string $api_key, ?string $base_url, Client $client)
    {
        $this->api_key = $api_key;
        $this->base_url = $base_url;
        $this->client = $client;
    }

    /**
     * Determine if the Currency Exchange Rate feature is enabled.
     */
    public function isEnabled(): bool
    {
        return filled($this->api_key) && filled($this->base_url);
    }

    public function getSupportedCurrencies(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return Cache::remember('supported_currency_codes', now()->addMonth(), function () {
            $response = $this->client->get("{$this->base_url}/{$this->api_key}/codes");

            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($responseData['result'] === 'success' && filled($responseData['supported_codes'])) {
                    return array_column($responseData['supported_codes'], 0);
                }
            }

            Log::error('Failed to retrieve supported currencies from Currency API', [
                'status_code' => $response->getStatusCode(),
                'response_body' => $response->getBody()->getContents(),
            ]);

            return null;
        });
    }

    public function getExchangeRates(string $baseCurrency, array $targetCurrencies): ?array
    {
        $cacheKey = "currency_rates_{$baseCurrency}";
        $cachedRates = Cache::get($cacheKey);

        if (Cache::missing($cachedRates)) {
            $cachedRates = $this->updateCurrencyRatesCache($baseCurrency);

            if (empty($cachedRates)) {
                return null;
            }
        }

        $filteredRates = array_intersect_key($cachedRates, array_flip($targetCurrencies));
        $filteredRates = array_filter($filteredRates);

        $filteredCurrencies = array_keys($filteredRates);
        $missingCurrencies = array_diff($targetCurrencies, $filteredCurrencies);

        if (filled($missingCurrencies)) {
            return null;
        }

        return $filteredRates;
    }

    public function getCachedExchangeRates(string $baseCurrency, array $targetCurrencies): ?array
    {
        if ($this->isEnabled()) {
            return $this->getExchangeRates($baseCurrency, $targetCurrencies);
        }

        return null;
    }

    public function getCachedExchangeRate(string $baseCurrency, string $targetCurrency): ?float
    {
        $rates = $this->getCachedExchangeRates($baseCurrency, [$targetCurrency]);

        if (isset($rates[$targetCurrency])) {
            return (float) $rates[$targetCurrency];
        }

        return null;
    }

    public function updateCurrencyRatesCache(string $baseCurrency): ?array
    {
        try {
            $response = $this->client->get("{$this->base_url}/{$this->api_key}/latest/{$baseCurrency}");

            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                if ($responseData['result'] === 'success' && isset($responseData['conversion_rates'])) {
                    Cache::put("currency_rates_{$baseCurrency}", $responseData['conversion_rates'], now()->addDay());

                    return $responseData['conversion_rates'];
                }

                $errorType = $responseData['error-type'] ?? 'unknown';

                Log::error('API returned error', [
                    'error_type' => $errorType,
                    'response_body' => $responseData,
                ]);
            } else {
                Log::error('Failed to retrieve exchange rates from Currency API', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $response->getBody()->getContents(),
                ]);
            }
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve exchange rates from Currency API', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        }

        return null;
    }
}
