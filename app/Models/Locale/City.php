<?php

namespace App\Models\Locale;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Squire\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $state_id
 * @property string $state_code
 * @property string $country_id
 * @property float $latitude
 * @property float $longitude
 */
class City extends Model
{
    public static array $schema = [
        'id' => 'integer',
        'name' => 'string',
        'state_id' => 'integer',
        'state_code' => 'string',
        'country_id' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public static function getCitiesByCountryAndState(?string $countryCode, ?string $stateId): Collection
    {
        if ($stateId === null || $countryCode === null) {
            return collect();
        }

        return self::query()->where('country_id', $countryCode)
            ->where('state_id', $stateId)
            ->get();
    }

    public static function getCityOptions(?string $countryCode = null, ?string $stateId = null): Collection
    {
        if ($countryCode === null || $stateId === null) {
            return collect();
        }

        return self::getCitiesByCountryAndState($countryCode, $stateId)->pluck('name', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
