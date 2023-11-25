# ERPSAAS

![Screenshot 2023-11-25 at 3 29 26 AM](https://github.com/andrewdwallo/erpsaas/assets/104294090/d1c8ed6d-4fd2-4c88-a02b-0f1534700b0f)
![Screenshot 2023-11-25 at 3 27 11 AM](https://github.com/andrewdwallo/erpsaas/assets/104294090/20b12920-1ca8-42ed-8c55-c034cde683b1)

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

## Live Currency

### Overview

This application offers support for real-time currency exchange rates. This feature is disabled by default. To enable it, you must first register for an API key at [ExchangeRate-API](https://www.exchangerate-api.com/). The application uses this service due to its generous provision of up to 1,500 free API calls per month, which should be enough for development and testing purposes.

**Disclaimer**: There is no affiliation between this application and ExchangeRate-API.

Once you have your API key, you can enable the feature by setting the `CURRENCY_API_KEY` environment variable in your `.env` file.

### Initial Setup

After setting your API key in the `.env` file, it is essential to prepare your database to store the currency data. Start by running a fresh database migration:

```bash
php artisan migrate:fresh
```

This ensures that your database is in the correct state to store the currency information. Afterward, use the following command to generate and populate the Currency List with supported currencies for the Live Currency page:

```bash
php artisan currency:init
```

This command fetches and stores the list of currencies supported by your configured exchange rate service.

### Configuration

Of course, you may use any service you wish to retrieve currency exchange rates. If you decide to use a different service, you can update the `config/services.php` file with your choice:

```php
'currency_api' => [
    'key' => env('CURRENCY_API_KEY'),
    'base_url' => 'https://v6.exchangerate-api.com/v6',
],
```

Then, adjust the implementation of the `App\Services\CurrencyService` class to use your chosen service.

### Live Currency Page

Once enabled, the "Live Currency" feature provides access to a dedicated page in the application, listing all supported currencies from the configured exchange rate service. Users can view available currencies and update exchange rates for their company's currencies as needed.

### Important Information

- To use the currency exchange rate feature, you must first obtain an API key from a service provider. This application is configured to use a service that offers a free tier suitable for development and testing purposes.
- Your API key is sensitive information and should be kept secret. Do not commit it to your repository or share it with anyone.
- Note that API rate limits may apply depending on the service you choose. Make sure to review the terms for your chosen service.

## Automatic Translation

The application now supports automatic translation, leveraging machine translation services provided by AWS, as facilitated by the [andrewdwallo/transmatic](https://github.com/andrewdwallo/transmatic) package. This integration significantly enhances the application's accessibility for a global audience. The application currently offers support for several languages, including English, Arabic, German, Spanish, French, Indonesian, Italian, Dutch, Portuguese, Turkish, and Chinese, with English as the default language.

### Configuration & Usage

To utilize this feature for additional languages or custom translations:
1. Follow the documentation provided in the [andrewdwallo/transmatic](https://github.com/andrewdwallo/transmatic) package.
2. Configure the package with your preferred translation service credentials.
3. Run the translation commands as per the package instructions to generate new translations.

Once you have configured the package, you may update the following method in the `app/Models/Setting/Localization.php` file to generate translations based on the selected language in the application UI:

Change to the following:
```php
public static function getAllLanguages(): array
{
    return Languages::getNames(app()->getLocale());
}
```

## Dependencies

- [filamentphp/filament](https://github.com/filamentphp/filament) - A collection of beautiful full-stack components
- [andrewdwallo/filament-companies](https://github.com/andrewdwallo/filament-companies) - A complete authentication system kit based on companies built for Filament
- [andrewdwallo/transmatic](https://github.com/andrewdwallo/transmatic) - A package for automatic translation using machine translation services
- [akaunting/laravel-money](https://github.com/akaunting/laravel-money) - Currency formatting and conversion package for Laravel
- [squirephp/squire](https://github.com/squirephp/squire) - A library of static Eloquent models for common fixture data

***Note*** : It is recommended to read the documentation for all dependencies to get yourself familiar with how the application works.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
