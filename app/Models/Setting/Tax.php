<?php

namespace App\Models\Setting;

use App\Casts\RateCast;
use App\Enums\TaxComputation;
use App\Enums\TaxScope;
use App\Enums\TaxType;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use App\Traits\HasDefault;
use App\Traits\SyncsWithCompanyDefaults;
use Database\Factories\Setting\TaxFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Tax extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasDefault;
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
