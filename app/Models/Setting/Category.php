<?php

namespace App\Models\Setting;

use App\Enums\CategoryType;
use App\Traits\{Blamable, CompanyOwned, SyncsWithCompanyDefaults};
use Database\Factories\Setting\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\{Factory, HasFactory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne};
use Wallo\FilamentCompanies\FilamentCompanies;

class Category extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;
    use SyncsWithCompanyDefaults;

    protected $table = 'categories';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => CategoryType::class,
        'enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function defaultIncomeCategory(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'income_category_id');
    }

    public function defaultExpenseCategory(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'expense_category_id');
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
        return CategoryFactory::new();
    }
}
