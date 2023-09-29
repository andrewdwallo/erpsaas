<?php

namespace App\Providers;

use App\Models\Locale\{City, Country, State, Timezone};
use Illuminate\Support\ServiceProvider;
use Squire\Repository;

class SquireServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        Repository::registerSource(Country::class, 'en', resource_path('data/countries.csv'));
        Repository::registerSource(State::class, 'en', resource_path('data/states.csv'));
        Repository::registerSource(City::class, 'en', resource_path('data/cities.csv'));
        Repository::registerSource(Timezone::class, 'en', resource_path('data/timezones.csv'));
    }
}
