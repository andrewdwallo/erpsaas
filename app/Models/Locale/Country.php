<?php

namespace App\Models\Locale;

use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Collection;
use Squire\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $iso_code_3
 * @property string $iso_code_2
 * @property int $numeric_code
 * @property string $phone_code
 * @property string $capital
 * @property string $currency_code
 * @property string $native_name
 * @property string $nationality
 * @property float $latitude
 * @property float $longitude
 * @property string $flag
 */
class Country extends Model
{
    public static array $schema = [
        'id' => 'integer',
        'name' => 'string',
        'iso_code_3' => 'string',
        'iso_code_2' => 'string',
        'numeric_code' => 'integer',
        'phone_code' => 'string',
        'capital' => 'string',
        'currency_code' => 'string',
        'native_name' => 'string',
        'nationality' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'flag' => 'string',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id', 'id');
    }

    public function timezones(): HasMany
    {
        return $this->hasMany(Timezone::class, 'country_id', 'id');
    }

    public static function findByIsoCode2(string $code): ?self
    {
        return self::where('iso_code_2', $code)->first();
    }

    public static function getAllCountryCodes(): Collection
    {
        return self::all()->pluck('iso_code_2');
    }

    public static function getAvailableCountryOptions(): array
    {
        return self::all()->mapWithKeys(static function ($country): array {
            return [$country->iso_code_2 => $country->name . ' ' . $country->flag];
        })->toArray();
    }
}
