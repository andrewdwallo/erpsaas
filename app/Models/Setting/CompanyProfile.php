<?php

namespace App\Models\Setting;

use App\Enums\EntityType;
use App\Models\Locale\Country;
use App\Traits\{Blamable, CompanyOwned};
use Database\Factories\Setting\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Factories\{Factory, HasFactory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return Country::findByIsoCode2($this->country)?->name ?? '';
    }

    protected static function newFactory(): Factory
    {
        return CompanyProfileFactory::new();
    }
}
