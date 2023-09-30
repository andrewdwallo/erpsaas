# ERPSAAS

![Screenshot 2023-09-17 at 5 31 58 PM](https://github.com/andrewdwallo/erpsaas/assets/104294090/647333c0-978c-4e24-92a8-30ee4932c092)

![Screenshot 2023-09-17 at 5 29 08 PM](https://github.com/andrewdwallo/erpsaas/assets/104294090/121326e6-8e43-4c4c-8f5b-650fc159b2d0)

This repo is currently a work in progress — PRs and issues welcome!

# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/10.x)

Clone the repository

    git clone https://github.com/andrewdwallo/erpsaas.git

Switch to the repo folder

    cd erpsaas

Install all the dependencies using composer and npm

    composer install
    npm install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

Build your dependencies & start the local development server

    npm run build
    npm run dev

**Command list**

    git clone https://github.com/andrewdwallo/erpsaas.git
    cd erpsaas
    composer install
    npm install
    cp .env.example .env
    php artisan key:generate
    php artisan migrate
    npm run build
    npm run dev

## Database seeding

**You may populate the database to help you get started quickly**

Open the DatabaseSeeder and set the property values as per your requirement

    database/seeders/DatabaseSeeder.php

Default login information:

    email: admin@gmail.com
    password: password

Run the database seeder

    php artisan db:seed

***Note*** : It's recommended to have a clean database before seeding. You can refresh your migrations at any point to clean the database by running the following command

    php artisan migrate:refresh

## Currency Exchange Rates

### Overview

This application offers support for real-time currency exchange rates. This feature is disabled by default. To enable it, you must first register for an API key at [ExchangeRate-API](https://www.exchangerate-api.com/). The application uses this service due to its generous provision of up to 1,500 free API calls per month, which should be enough for development and testing purposes.

**Disclaimer**: There is no affiliation between this application and ExchangeRate-API.

Once you have your API key, you can enable the feature by setting the `CURRENCY_API_KEY` environment variable in your `.env` file.

### Configuration

Of course, you may use any service you wish to retrieve currency exchange rates. If you decide to use a different service, you can update the `config/services.php` file with your choice:

```php
'currency_api' => [
    'key' => env('CURRENCY_API_KEY'),
    'base_url' => 'https://v6.exchangerate-api.com/v6',
],
```

Additionally, you may update the following method in the `app/Services/CurrencyService.php` file which is responsible for retrieving the exchange rate:

```php
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
```

### Important Information

- To use the currency exchange rate feature, you must first obtain an API key from a service provider. This application is configured to use a service that offers a free tier suitable for development and testing purposes.
- Your API key is sensitive information and should be kept secret. Do not commit it to your repository or share it with anyone.
- Note that API rate limits may apply depending on the service you choose. Make sure to review the terms for your chosen service.

## Dependencies

- [filamentphp/filament](https://github.com/filamentphp/filament) - A collection of beautiful full-stack components
- [andrewdwallo/filament-companies](https://github.com/andrewdwallo/filament-companies) - A complete authentication system kit based on companies built for Filament
- [akaunting/laravel-money](https://github.com/akaunting/laravel-money) - Currency formatting and conversion package for Laravel
- [squirephp/squire](https://github.com/squirephp/squire) - A library of static Eloquent models for common fixture data.

***Note*** : It is recommended to read the documentation for all dependencies to get yourself familiar with how the application works.
