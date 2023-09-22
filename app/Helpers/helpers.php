<?php

use App\Helpers\LocationDataLoader;
use Illuminate\Support\Collection;

if (!function_exists('country')) {
    function country($countryCode, $hydrate = true)
    {
        return LocationDataLoader::getCountry($countryCode, $hydrate);
    }
}

if (!function_exists('countries')) {
    function countries($hydrate = true): Collection
    {
        return LocationDataLoader::getAllCountries($hydrate);
    }
}

if (!function_exists('state')) {
    function state($stateCode, $hydrate = true)
    {
        return LocationDataLoader::getState($stateCode, $hydrate);
    }
}

if (!function_exists('states')) {
    function states($countryCode, $hydrate = true): Collection
    {
        return LocationDataLoader::getAllStates($countryCode, $hydrate);
    }
}

if (!function_exists('city')) {
    function city($cityId, $hydrate = true)
    {
        return LocationDataLoader::getCity($cityId, $hydrate);
    }
}

if (!function_exists('cities')) {
    function cities($countryCode, $stateCode, $hydrate = true): Collection
    {
        return LocationDataLoader::getAllCities($countryCode, $stateCode, $hydrate);
    }
}
