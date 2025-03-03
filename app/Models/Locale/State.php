<?php

namespace App\Models\Locale;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Squire\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $country_id
 * @property string $country_name
 * @property string $state_code
 * @property float $latitude
 * @property float $longitude
 */
class State extends Model
{
    public static array $schema = [
        'id' => 'integer',
        'name' => 'string',
        'country_id' => 'string',
        'country_name' => 'string',
        'state_code' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public static function getStateOptions(?string $code = null): array
    {
        if (! $code) {
            return [];
        }

        return self::query()
            ->where('country_id', $code)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getSearchResultsUsing(string $search, ?string $countryCode = null): array
    {
        if (! $countryCode) {
            return [];
        }

        return self::query()
            ->where('country_id', $countryCode)
            ->where(static function ($query) use ($search) {
                $query->whereLike('name', "%{$search}%")
                    ->orWhereLike('state_code', "%{$search}%");
            })
            ->orderByRaw('
                CASE
                    WHEN state_code = ? THEN 1
                    WHEN state_code LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END
            ', [$search, $search . '%', $search . '%'])
            ->limit(50)
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }
}
