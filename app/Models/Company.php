<?php

namespace App\Models;

use App\Enums\Accounting\DocumentType;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Models\Banking\ConnectedBankAccount;
use App\Models\Common\Client;
use App\Models\Common\Contact;
use App\Models\Common\Offering;
use App\Models\Core\Department;
use App\Models\Setting\CompanyDefault;
use App\Models\Setting\CompanyProfile;
use App\Models\Setting\Currency;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Localization;
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

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile->logo_url ?? $this->owner->profile_photo_url;
    }

    public function connectedBankAccounts(): HasMany
    {
        return $this->hasMany(ConnectedBankAccount::class, 'company_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Accounting\Account::class, 'company_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Common\Address::class, 'company_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(Accounting\Adjustment::class, 'company_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'company_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Accounting\Bill::class, 'company_id');
    }

    public function accountSubtypes(): HasMany
    {
        return $this->hasMany(AccountSubtype::class, 'company_id');

    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'company_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'company_id');
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class, 'company_id');
    }

    public function default(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'company_id');
    }

    public function documentDefaults(): HasMany
    {
        return $this->hasMany(DocumentDefault::class, 'company_id');
    }

    public function defaultBill(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::Bill);
    }

    public function defaultEstimate(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::Estimate);
    }

    public function defaultInvoice(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::Invoice);
    }

    public function defaultRecurringInvoice(): HasOne
    {
        return $this->hasOne(DocumentDefault::class, 'company_id')
            ->where('type', DocumentType::RecurringInvoice);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'company_id');
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Accounting\Estimate::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Accounting\Invoice::class, 'company_id');
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(Accounting\RecurringInvoice::class, 'company_id');
    }

    public function locale(): HasOne
    {
        return $this->hasOne(Localization::class, 'company_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class, 'company_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Accounting\Transaction::class, 'company_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class, 'company_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Common\Vendor::class, 'company_id');
    }
}
