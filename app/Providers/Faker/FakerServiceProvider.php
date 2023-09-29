<?php

namespace App\Providers\Faker;

use App\Faker\{PhoneNumber, State};
use Faker\{Factory, Generator};
use Illuminate\Support\ServiceProvider;

class FakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Generator::class, static function () {
            $faker = Factory::create();
            $faker->addProvider(new PhoneNumber($faker));
            $faker->addProvider(new State($faker));

            return $faker;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
