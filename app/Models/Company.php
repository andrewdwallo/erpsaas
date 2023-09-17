<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Banking\Account;
use App\Models\Setting\Appearance;
use App\Models\Setting\Category;
use App\Models\Setting\CompanyDefault;
use App\Models\Setting\CompanyProfile;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\Company as FilamentCompaniesCompany;
use Wallo\FilamentCompanies\Events\CompanyCreated;
use Wallo\FilamentCompanies\Events\CompanyDeleted;
use Wallo\FilamentCompanies\Events\CompanyUpdated;

class Company extends FilamentCompaniesCompany implements HasAvatar
{
    use HasFactory;

    public function getFilamentAvatarUrl(): string
    {
        return $this->owner->profile_photo_url;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'personal_company' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'personal_company',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => CompanyCreated::class,
        'updated' => CompanyUpdated::class,
        'deleted' => CompanyDeleted::class,
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'company_id');
    }

    public function appearance(): HasOne
    {
        return $this->hasOne(Appearance::class, 'company_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'company_id');
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class, 'company_id');
    }

    public function default(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'company_id');
    }

    public function defaultBill(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::Bill);
    }

    public function defaultInvoice(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::Invoice);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'company_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'company_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class, 'company_id');
    }
}
