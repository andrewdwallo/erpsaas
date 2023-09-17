<?php

namespace App\Models\Setting;

use App\Enums\EntityType;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Setting\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class CompanyProfile extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'company_profiles';

    protected $fillable = [
        'company_id',
        'logo',
        'address',
        'city',
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
        return country($this->country)->getName();
    }

    public static function getAvailableCountryOptions(): array
    {
        $allCountries = countries();
        $countryData = [];

        foreach ($allCountries as $code => $countryDetails) {
            $name = $countryDetails['name'];
            $emoji = $countryDetails['emoji'];
            $countryData[$code] = $name . ' ' . $emoji;
        }

        return $countryData;
    }

    public static function getAvailableCountryNames(): array
    {
        $allCountries = countries();
        $names = [];

        foreach ($allCountries as $country) {
            $names[] = $country['name'];
        }

        return $names;
    }

    public static function getAvailableCountryEmojis(): array
    {
        $allCountries = countries();
        $emojis = [];

        foreach ($allCountries as $country) {
            $emojis[] = $country['emoji'];
        }

        return $emojis;
    }

    public static function getStateOptions(string $countryCode): array
    {
        $states = country($countryCode)->getDivisions();

        return collect($states)->mapWithKeys(static function ($state, $code): array {
            return [$code => $state['name']];
        })->toArray();
    }

    public static function getTimezoneOptions(string $countryCode): array
    {
        $timezones = country($countryCode)->getTimezones();

        if ($timezones) {
            return collect($timezones)->mapWithKeys(static function ($timezone): array {
                return [$timezone => $timezone];
            })->toArray();
        }

        return [];
    }

    protected static function newFactory(): Factory
    {
        return CompanyProfileFactory::new();
    }
}
