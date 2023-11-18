<?php

namespace App\Models\Common;

use App\Enums\ContactType;
use App\Models\Setting\Currency;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Common\ContactFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Contact extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'email',
        'address',
        'city_id',
        'zip_code',
        'state_id',
        'country',
        'timezone',
        'language',
        'contact_method',
        'phone_number',
        'tax_id',
        'currency_code',
        'website',
        'reference',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => ContactType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code');
    }

    public function employeeship(): HasOne
    {
        return $this->hasOne(FilamentCompanies::employeeshipModel(), 'contact_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    protected static function newFactory(): Factory
    {
        return ContactFactory::new();
    }
}
