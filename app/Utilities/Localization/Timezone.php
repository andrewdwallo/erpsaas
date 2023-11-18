<?php

namespace App\Utilities\Localization;

use App\Enums\TimeFormat;
use App\Models\Setting\Localization;
use Symfony\Component\Intl\Timezones;

class Timezone
{
    public static function getTimezoneOptions(?string $countryCode = null): array
    {
        if (empty($countryCode)) {
            return [];
        }

        $timezones = Timezones::forCountryCode($countryCode);

        if (empty($timezones)) {
            return [];
        }

        $results = [];

        foreach ($timezones as $timezone) {
            $translatedName = Timezones::getName($timezone);
            $cityName = self::extractCityName($translatedName);
            $localTime = self::getLocalTime($timezone);
            $timezoneAbbreviation = now($timezone)->format('T');

            $results[$timezone] = "{$cityName} ({$timezoneAbbreviation}) {$localTime}";
        }

        return $results;
    }

    public static function extractCityName(string $translatedName): string
    {
        if (preg_match('/\((.*?)\)/', $translatedName, $match)) {
            return trim($match[1]);
        }

        return $translatedName;
    }

    public static function getLocalTime(string $timezone): string
    {
        $localizationModel = Localization::firstOrFail();
        $time_format = $localizationModel->time_format->value ?? TimeFormat::DEFAULT;

        return now($timezone)->translatedFormat($time_format);
    }
}
