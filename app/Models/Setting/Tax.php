<?php

namespace App\Models\Setting;

use App\Models\Company;
use App\Models\Document\DocumentItem;
use App\Models\Item;
use App\Scopes\CurrentCompanyScope;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Tax extends Model
{
    use HasFactory;

    protected $table = 'taxes';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'rate',
        'computation',
        'type',
        'scope',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function defaultSalesTax(): HasOne
    {
        return $this->hasOne(DefaultSetting::class, 'sales_tax_id');
    }

    public function defaultPurchaseTax(): HasOne
    {
        return $this->hasOne(DefaultSetting::class, 'purchase_tax_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function document_items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function bill_items(): HasMany
    {
        return $this->document_items()->where('type', 'bill');
    }

    public function invoice_items(): HasMany
    {
        return $this->document_items()->where('type', 'invoice');
    }

    public static function getComputationTypes(): array
    {
        return [
            'fixed' => 'Fixed',
            'percentage' => 'Percentage',
            'compound' => 'Compound',
        ];
    }

    public static function getTaxTypes(): array
    {
        return [
            'sales' => 'Sales',
            'purchase' => 'Purchase',
            'none' => 'None',
        ];
    }

    public static function getTaxScopes(): array
    {
        return [
            'product' => 'Product',
            'service' => 'Service',
        ];
    }

    protected static function newFactory(): Factory
    {
        return TaxFactory::new();
    }
}
