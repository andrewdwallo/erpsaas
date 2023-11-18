<?php

namespace App\Models\Locale;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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

    public static function getStateOptions(?string $code = null): Collection
    {
        if ($code === null) {
            return collect();
        }

        return self::where('country_id', $code)->get()->pluck('name', 'id');
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
