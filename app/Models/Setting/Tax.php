<?php

namespace App\Models\Setting;

use App\Casts\RateCast;
use App\Enums\{TaxComputation, TaxScope, TaxType};
use App\Traits\{Blamable, CompanyOwned, SyncsWithCompanyDefaults};
use Database\Factories\Setting\TaxFactory;
use Illuminate\Database\Eloquent\Factories\{Factory, HasFactory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};
use Wallo\FilamentCompanies\FilamentCompanies;

class Tax extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;
    use SyncsWithCompanyDefaults;

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
        'rate' => RateCast::class,
        'computation' => TaxComputation::class,
        'type' => TaxType::class,
        'scope' => TaxScope::class,
        'enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function defaultSalesTax(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'sales_tax_id');
    }

    public function defaultPurchaseTax(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'purchase_tax_id');
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
        return TaxFactory::new();
    }
}
