<?php

namespace App\Models;

use App\Models\Document\Document;
use App\Models\Setting\Currency;
use App\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Squire\Models\Country;
use Squire\Models\Region;
use Wallo\FilamentCompanies\FilamentCompanies;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        'company_id',
        'entity',
        'type',
        'name',
        'email',
        'tax_number',
        'phone',
        'address',
        'city',
        'zip_code',
        'state',
        'country',
        'website',
        'currency_code',
        'reference',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope);
    }

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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public static function getCountryOptions(): array
    {
        $allCountries = Country::all();

        // Default countries to show at the top of the options list
        $defaultCountryNames = ['United States', 'Canada', 'United Kingdom', 'Australia']; // replace with actual country names

        $defaultCountryOptions = [];
        $countryOptions = [];

        foreach ($allCountries as $country) {
            if (in_array($country->name, $defaultCountryNames, true)) {
                $defaultCountryOptions[$country->name] = $country->name;
            } else {
                $countryOptions[$country->name] = $country->name;
            }
        }

        // Guarantee the order of default countries
        $orderedDefaultCountryOptions = [];
        foreach ($defaultCountryNames as $name) {
            if (isset($defaultCountryOptions[$name])) {
                $orderedDefaultCountryOptions[$name] = $defaultCountryOptions[$name];
            }
        }

        return $orderedDefaultCountryOptions + $countryOptions;
    }

    public static function getRegionOptions(string $countryName): array
    {
        $country = Country::where('name', $countryName)->first();

        if (!$country) {
            return [];
        }

        return Region::where('country_id', $country->id)
            ->pluck('name', 'name')
            ->toArray();
    }

    public function bills(): HasMany
    {
        return $this->documents()->where('type', 'bill');
    }

    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    public function scopeVendor($query)
    {
        return $query->where('type', 'vendor');
    }

    public function scopeCustomer($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeCompany($query)
    {
        return $query->where('entity', 'company');
    }

    public function scopeIndividual($query)
    {
        return $query->where('entity', 'individual');
    }
}
