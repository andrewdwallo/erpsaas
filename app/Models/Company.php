<?php

namespace App\Models;

use App\Models\Banking\Account;
use App\Models\Document\Document;
use App\Models\Document\DocumentItem;
use App\Models\Document\DocumentTotal;
use App\Models\Setting\Currency;
use App\Models\Setting\Category;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\Company as FilamentCompaniesCompany;
use Wallo\FilamentCompanies\Events\CompanyCreated;
use Wallo\FilamentCompanies\Events\CompanyDeleted;
use Wallo\FilamentCompanies\Events\CompanyUpdated;

class Company extends FilamentCompaniesCompany
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

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function customers(): HasMany
    {
        return $this->contacts()->where('type', 'customer');
    }

    public function company_customers(): HasMany
    {
        return $this->contacts()->where('type', 'customer')
            ->where('entity', 'company');
    }

    public function individual_customers(): HasMany
    {
        return $this->contacts()->where('type', 'customer')
            ->where('entity', 'individual');
    }

    public function vendors(): HasMany
    {
        return $this->contacts()->where('type', 'vendor');
    }

    public function company_vendors(): HasMany
    {
        return $this->contacts()->where('type', 'vendor')
            ->where('entity', 'company');
    }

    public function individual_vendors(): HasMany
    {
        return $this->contacts()->where('type', 'vendor')
            ->where('entity', 'individual');
    }

    public function document_defaults(): HasOne
    {
        return $this->hasOne(DocumentDefault::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function document_items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function document_totals(): HasMany
    {
        return $this->hasMany(DocumentTotal::class);
    }

    public function bills(): HasMany
    {
        return $this->documents()->where('type', 'bill');
    }

    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    public function bill_items(): HasMany
    {
        return $this->document_items()->where('type', 'bill');
    }

    public function bill_totals(): HasMany
    {
        return $this->document_totals()->where('type', 'bill');
    }

    public function invoice_items(): HasMany
    {
        return $this->document_items()->where('type', 'invoice');
    }

    public function invoice_totals(): HasMany
    {
        return $this->document_totals()->where('type', 'invoice');
    }
}
