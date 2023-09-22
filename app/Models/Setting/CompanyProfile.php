<?php

namespace App\Models\Setting;

use App\Enums\EntityType;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Setting\CompanyProfileFactory;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Squire\Models\Timezone;
use Wallo\FilamentCompanies\FilamentCompanies;

class CompanyProfile extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'company_profiles';

    protected $fillable = [
        'company_id',
        'logo',
        'address',
        'city_id',
        'zip_code',
        'state',
        'country',
        'timezone',
        'phone_number',
        'email',
        'tax_id',
        'entity_type',
        'fiscal_year_start',
        'fiscal_year_end',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entity_type' => EntityType::class,
        'fiscal_year_start' => 'date',
        'fiscal_year_end' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }


    public function getCountryName(): string
    {
        return country($this->country)?->getName();
    }

    public static function getAvailableCountryCodes(): array
    {
        return countries()->pluck('iso2')->toArray();
    }

    public static function getAvailableCountryOptions(): array
    {
        $countries = countries();

        return $countries->mapWithKeys(static function ($country): array {
            return [$country['iso2'] => $country['name'] . ' ' . $country['emoji']];
        })->toArray();
    }

    public static function getAvailableCountryNames(): array
    {
        return countries()->pluck('name')->toArray();
    }

    public static function getAvailableCountryEmojis(): array
    {
        return countries()->pluck('emoji')->toArray();
    }

    public static function getStateOptions(?string $countryCode = null): array
    {
        if (empty($countryCode)) {
            return [];
        }

        $states = states($countryCode);

        return $states->mapWithKeys(static function ($state): array {
            return [$state['state_code'] => $state['name']];
        })->toArray();
    }

    public static function getCityOptions(?string $countryCode = null, ?string $stateCode = null): array
    {
        if (empty($countryCode) || empty($stateCode)) {
            return [];
        }

        $cities = cities($countryCode, $stateCode);

        return $cities->mapWithKeys(static function ($city): array {
            return [$city['id'] => $city['name']];
        })->toArray();
    }

    public static function getTimezoneOptions(?string $countryCode = null): array
    {
        if (empty($countryCode)) {
            return [];
        }

        // convert countryCode to lowercase
        $countryCode = strtolower($countryCode);

        $timezones = Timezone::where('country_id', $countryCode)->get();

        if (!$timezones->isEmpty()) {
            return $timezones->mapWithKeys(static function ($timezone): array {
                $localTime = self::getLocalTime($timezone->code);
                $parts = explode('/', $timezone->code);
                $cityName = str_replace('_', ' ', end($parts));
                $offsetInSeconds = $timezone->getOffset(now());
                $hours = floor($offsetInSeconds / 3600);
                $minutes = floor(($offsetInSeconds / 60) % 60);
                $gmtOffsetName = sprintf("UTC%+d:%02d", $hours, $minutes);

                return [$timezone->code => $cityName . ' (' . $gmtOffsetName . ') ' . $localTime];
            })->toArray();
        }

        return [];
    }

    /**
     * @throws Exception
     */
    public static function getLocalTime(string $timezone): string
    {
        return (new DateTime('now', new DateTimeZone($timezone)))->format('g:i A');
    }

    protected static function newFactory(): Factory
    {
        return CompanyProfileFactory::new();
    }
}
