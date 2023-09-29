<?php

namespace App\Console\Commands;

use App\Utilities\ModelCacheManager;
use Illuminate\Console\Command;

class CacheData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache data from CSV files into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // ModelCacheManager::cacheData(resource_path('data/countries.csv'), 'countries');
        ModelCacheManager::cacheData(resource_path('data/currencies.csv'), 'currencies');
        // ModelCacheManager::cacheData(resource_path('data/states.csv'), 'states');
        // ModelCacheManager::cacheData(resource_path('data/cities.csv'), 'cities');

        $this->info('Data cached successfully.');
    }
}
