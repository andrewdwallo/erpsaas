<?php

namespace App\Models\Locale;

use App\Models\Common\Address;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Squire\Model;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locales;

/**
 * @property string $id
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
        'id' => 'string',
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

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'country_code', 'id');
    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'country_id', 'id');
    }

    protected function name(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): string {
            $exists = Countries::exists($attributes['id']);

            return $exists ? Countries::getName($attributes['id']) : $value;
        });
    }

    public static function findByIsoCode2(string $code): ?self
    {
        return self::where('id', $code)->first();
    }

    public static function getAllCountryCodes(): Collection
    {
        return self::query()
            ->select('id')
            ->pluck('id');
    }

    public static function getAvailableCountryOptions(): array
    {
        return self::query()
            ->select(['id', 'name', 'flag'])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(static fn ($country) => [
                $country->id => $country->name . ' ' . $country->flag,
            ])
            ->toArray();
    }

    public static function getSearchResultsUsing(string $search): array
    {
        return self::query()
            ->select(['id', 'name', 'flag'])
            ->where(static function ($query) use ($search) {
                $query->whereLike('name', "%{$search}%")
                    ->orWhereLike('id', "%{$search}%");
            })
            ->orderByRaw('
                CASE
                    WHEN id = ? THEN 1
                    WHEN id LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END
            ', [$search, $search . '%', $search . '%'])
            ->limit(50)
            ->get()
            ->mapWithKeys(static fn (self $country) => [
                $country->id => $country->name . ' ' . $country->flag,
            ])
            ->toArray();
    }

    public static function getLanguagesByCountryCode(?string $code = null): array
    {
        if (! $code) {
            return Locales::getNames();
        }

        $locales = Locales::getNames();
        $languages = [];

        foreach (array_keys($locales) as $locale) {
            $localeRegion = locale_get_region($locale);
            $localeLanguage = locale_get_primary_language($locale);

            if ($localeRegion === $code) {
                $languages[$localeLanguage] = Locales::getName($localeLanguage);
            }
        }

        return $languages;
    }
}
