<?php

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationDataLoader
{
    protected static ?array $countries = null;
    protected static ?array $states = null;
    protected static ?array $cities = null;

    public static function loadData(string $type): void
    {
        // Try to get data from cache first.
        static::${$type} = Cache::remember("location_data_{$type}", now()->addMinutes(30), static function () use ($type) {
            $csvPath = resource_path("data/{$type}.csv");
            $data = [];

            try {
                $handle = fopen($csvPath, 'rb');

                // Get the header of the CSV file
                $header = fgetcsv($handle);

                // Read each line of the CSV
                while (($row = fgetcsv($handle)) !== false) {
                    $data[] = array_combine($header, $row);
                }

                fclose($handle);
                return $data;
            } catch (\Exception $e) {
                Log::error("CSV reading failed for {$type}: {$e->getMessage()}");
                return [];
            }
        });
    }

    public static function getCountry($countryCode, $hydrate = true)
    {
        static::loadData('countries');
        $countryCode = strtoupper($countryCode);
        $country = collect(static::$countries)->firstWhere('iso2', $countryCode);

        return $hydrate ? new Country($country) : $country;
    }

    public static function getAllCountries($hydrate = true): Collection
    {
        static::loadData('countries');
        $countries = collect(static::$countries);

        return $hydrate ? $countries->map(static fn ($country) => new Country($country)) : $countries;
    }


    public static function getState($countryCode, $stateCode, $hydrate = true)
    {
        static::loadData('states');
        $countryCode = strtoupper($countryCode);
        $stateCode = strtoupper($stateCode);

        $state = collect(static::$states)->first(static function ($item) use ($countryCode, $stateCode) {
            return $item['country_code'] === $countryCode && $item['state_code'] === $stateCode;
        });

        if ($state) {
            return $hydrate ? new State($state) : $state;
        }

        return null;
    }


    public static function getAllStates($countryCode, $hydrate = true): Collection
    {
        static::loadData('states');
        $countryCode = strtoupper($countryCode);
        $states = collect(static::$states)->where('country_code', $countryCode);

        if ($states->isEmpty()) {
            return collect();
        }

        if ($hydrate) {
            return $states->map(static fn ($state) => new State($state));
        }

        return $states;
    }

    public static function getCity($cityId, $hydrate = true)
    {
        static::loadData('cities');
        $city = collect(static::$cities)->firstWhere('id', $cityId);

        if ($city) {
            return $hydrate ? new City($city) : $city;
        }

        return null;
    }

    public static function getAllCities($countryCode, $stateCode, $hydrate = true): Collection
    {
        static::loadData('cities');

        // Filter cities based on country and state codes
        $filteredCities = collect(static::$cities)
            ->where('country_code', strtoupper($countryCode))
            ->where('state_code', strtoupper($stateCode));

        if ($filteredCities->isEmpty()) {
            return collect();
        }

        return $hydrate ? $filteredCities->map(static fn ($city) => new City($city)) : $filteredCities;
    }

}
