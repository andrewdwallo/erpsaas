<?php

namespace App\Services;

use App\Models\Company;
use GuzzleHttp\Psr7\Message;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlaidService
{
    public const API_VERSION = '2020-09-14';

    protected ?string $client_id;

    protected ?string $client_secret;

    protected ?string $environment;

    protected ?string $base_url;

    protected HttpClient $client;

    protected Config $config;

    protected array $plaidSupportedLanguages = [
        'da', 'nl', 'en',
        'et', 'fr', 'de',
        'it', 'lv', 'lt',
        'no', 'pl', 'pt',
        'ro', 'es', 'sv',
    ];

    protected array $plaidSupportedCountries = [
        'US', 'GB', 'ES',
        'NL', 'FR', 'IE',
        'CA', 'DE', 'IT',
        'PL', 'DK', 'NO',
        'SE', 'EE', 'LT',
        'LV', 'PT', 'BE',
    ];

    public function __construct(HttpClient $client, Config $config)
    {
        $this->client = $client;
        $this->config = $config;
        $this->client_id = $this->config->get('plaid.client_id');
        $this->client_secret = $this->config->get('plaid.client_secret');
        $this->environment = $this->config->get('plaid.environment', 'sandbox');

        $this->setBaseUrl($this->environment);
    }

    public function setClientCredentials(?string $client_id, ?string $client_secret): self
    {
        $this->client_id = $client_id ?? $this->client_id;
        $this->client_secret = $client_secret ?? $this->client_secret;

        return $this;
    }

    public function setEnvironment(?string $environment): self
    {
        $this->environment = $environment ?? $this->environment;

        $this->setBaseUrl($this->environment);

        return $this;
    }

    public function setBaseUrl(?string $environment): void
    {
        $this->base_url = match ($environment) {
            'development' => 'https://development.plaid.com',
            'production' => 'https://production.plaid.com',
            default => 'https://sandbox.plaid.com', // Default to sandbox, including if environment is null
        };
    }

    public function getBaseUrl(): string
    {
        return $this->base_url;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function buildRequest(string $method, string $endpoint, array $data = []): Response
    {
        $request = $this->client->withHeaders([
            'Plaid-Version' => self::API_VERSION,
            'Content-Type' => 'application/json',
        ])->baseUrl($this->base_url);

        if ($method === 'post') {
            $request = $request->withHeaders([
                'PLAID-CLIENT-ID' => $this->client_id,
                'PLAID-SECRET' => $this->client_secret,
            ]);
        }

        return $request->{$method}($endpoint, $data);
    }

    public function sendRequest(string $endpoint, array $data = []): object
    {
        try {
            $response = $this->buildRequest('post', $endpoint, $data)->throw()->object();

            if ($response === null) {
                throw new RuntimeException('Plaid API returned null response.');
            }

            return $response;
        } catch (RequestException $e) {
            $statusCode = $e->response->status();

            $message = "Plaid API request returned status code {$statusCode}";

            $summary = Message::bodySummary($e->response->toPsrResponse(), 1000);

            if ($summary !== null) {
                $message .= ":\n{$summary}\n";
            }

            Log::error($message);

            throw new RuntimeException('An error occurred while communicating with the Plaid API.');
        }
    }

    public function createPlaidUser(Company $company): array
    {
        return array_filter([
            'client_user_id' => (string) $company->owner->id,
            'legal_name' => $company->owner->name,
            'phone_number' => $company->profile->phone_number,
            'email_address' => $company->owner->email,
        ], static fn ($value): bool => $value !== null);
    }

    public function getLanguage(string $language): string
    {
        if (in_array($language, $this->plaidSupportedLanguages, true)) {
            return $language;
        }

        return 'en';
    }

    public function getCountry(string $country): string
    {
        if (in_array($country, $this->plaidSupportedCountries, true)) {
            return $country;
        }

        return 'US';
    }

    public function createToken(string $language, string $country, array $user, array $products = []): object
    {
        $plaidLanguage = $this->getLanguage($language);

        $plaidCountry = $this->getCountry($country);

        return $this->createLinkToken(
            'ERPSAAS',
            $plaidLanguage,
            [$plaidCountry],
            $user,
            $products,
        );
    }

    public function createLinkToken(string $client_name, string $language, array $country_codes, array $user, array $products): object
    {
        $data = [
            'client_name' => $client_name,
            'language' => $language,
            'country_codes' => $country_codes,
            'user' => (object) $user,
        ];

        if ($products) {
            $data['products'] = $products;
        }

        return $this->sendRequest('link/token/create', $data);
    }

    public function exchangePublicToken(string $public_token): object
    {
        $data = compact('public_token');

        return $this->sendRequest('item/public_token/exchange', $data);
    }

    public function getAccounts(string $accessToken, array $options = []): object
    {
        $data = [
            'access_token' => $accessToken,
            'options' => (object) $options,
        ];

        return $this->sendRequest('accounts/get', $data);
    }

    public function getInstitution(string $institution_id, string $country): object
    {
        $options = [
            'include_optional_metadata' => true,
        ];

        $plaidCountry = $this->getCountry($country);

        return $this->getInstitutionById($institution_id, [$plaidCountry], $options);
    }

    public function getInstitutionById(string $institution_id, array $country_codes, array $options = []): object
    {
        $data = [
            'institution_id' => $institution_id,
            'country_codes' => $country_codes,
            'options' => (object) $options,
        ];

        return $this->sendRequest('institutions/get_by_id', $data);
    }

    public function getTransactions(string $access_token, string $start_date, string $end_date, array $options = []): object
    {
        $data = [
            'access_token' => $access_token,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'options' => (object) $options,
        ];

        return $this->sendRequest('transactions/get', $data);
    }
}
