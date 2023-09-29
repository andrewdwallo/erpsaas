<?php

namespace App\Models\Locale;

use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Squire\Model;

/**
 * @property int $id
 * @property int $country_id
 * @property string $country_code
 * @property string $name
 * @property int $gmt_offset
 * @property string $gmt_offset_name
 * @property string $abbreviation
 * @property string $tz_name
 */
class Timezone extends Model
{
    public static array $schema = [
        'id' => 'integer',
        'country_id' => 'integer',
        'country_code' => 'string',
        'name' => 'string',
        'gmt_offset' => 'integer',
        'gmt_offset_name' => 'string',
        'abbreviation' => 'string',
        'tz_name' => 'string',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public static function getTimezoneOptions(?string $countryCode = null): array
    {
        if (empty($countryCode)) {
            return [];
        }

        $timezones = self::where('country_code', $countryCode)->get();

        if ($timezones->isEmpty()) {
            return [];
        }

        return $timezones
            ->mapWithKeys(static function ($timezone) {
                $localTime = self::getLocalTime($timezone->name); // Adjust this as per your column name
                $cityName = str_replace('_', ' ', last(explode('/', $timezone->name))); // Adjust this as per your column name

                return [$timezone->name => "{$cityName} ({$timezone->abbreviation}) {$localTime}"];
            })
            ->toArray();
    }

    /**
     * @throws Exception
     */
    public static function getLocalTime(string $timezone): string
    {
        return (new DateTime('now', new DateTimeZone($timezone)))->format('g:i A');
    }
}
