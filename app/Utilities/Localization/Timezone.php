<?php

namespace App\Utilities\Localization;

use App\Enums\TimeFormat;
use App\Models\Setting\Localization;
use DateTimeZone;
use Symfony\Component\Intl\Timezones;

class Timezone
{
    public static function getTimezoneOptions(#[\SensitiveParameter] ?string $countryCode = null): array
    {
        if (empty($countryCode)) {
            return [];
        }

        $countryTimezones = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, strtoupper($countryCode));

        if (empty($countryTimezones)) {
            return [];
        }

        $localizedTimezoneNames = Timezones::getNames();

        $results = [];

        foreach ($countryTimezones as $timezoneIdentifier) {
            $translatedName = $localizedTimezoneNames[$timezoneIdentifier] ?? $timezoneIdentifier;
            $cityName = self::extractCityName($translatedName);
            $localTime = self::getLocalTime($timezoneIdentifier);
            $timezoneAbbreviation = now($timezoneIdentifier)->format('T');

            $results[$timezoneIdentifier] = "{$cityName} ({$timezoneAbbreviation}) {$localTime}";
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
