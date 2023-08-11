<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Contact;
use Squire\Repository;
use Illuminate\Support\ServiceProvider;

class SquireServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Repository::registerSource(Contact::class, 'en', base_path('vendor/squirephp/regions-en/resources/data.csv'));
        Repository::registerSource(Contact::class, 'en', base_path('vendor/squirephp/countries-en/resources/data.csv'));
    }
}
