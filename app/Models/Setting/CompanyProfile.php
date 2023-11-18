<?php

namespace App\Models\Setting;

use App\Enums\EntityType;
use App\Models\Locale\City;
use App\Models\Locale\Country;
use App\Models\Locale\State;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Setting\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Wallo\FilamentCompanies\FilamentCompanies;

class CompanyProfile extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'company_profiles';

    protected $fillable = [
        'company_id',
        'logo',
        'address',
        'city_id',
        'zip_code',
        'state_id',
        'country',
        'phone_number',
        'email',
        'tax_id',
        'entity_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entity_type' => EntityType::class,
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function logoUrl(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['logo'] ? Storage::disk('public')->url($attributes['logo']) : null;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
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
        return Country::findByIsoCode2($this->country)?->name ?? '';
    }

    protected static function newFactory(): Factory
    {
        return CompanyProfileFactory::new();
    }
}
