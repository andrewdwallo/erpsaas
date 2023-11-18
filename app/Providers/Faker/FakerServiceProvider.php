<?php

namespace App\Providers\Faker;

use App\Faker\CurrencyCode;
use App\Faker\PhoneNumber;
use App\Faker\State;
use Faker\Factory;
use Faker\Generator;
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
            $faker->addProvider(new CurrencyCode($faker));

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
