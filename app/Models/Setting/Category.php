<?php

namespace App\Models\Setting;

use App\Enums\CategoryType;
use App\Models\Accounting\Account;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use App\Traits\HasDefault;
use App\Traits\SyncsWithCompanyDefaults;
use Database\Factories\Setting\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Category extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasDefault;
    use HasFactory;
    use SyncsWithCompanyDefaults;

    protected $table = 'categories';

    protected $fillable = [
        'company_id',
        'account_id',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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
