<?php

namespace App\Models\Accounting;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Traits\CompanyOwned;
use Database\Factories\Accounting\AccountSubtypeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class AccountSubtype extends Model
{
    use CompanyOwned;
    use HasFactory;

    protected $table = 'account_subtypes';

    protected $fillable = [
        'company_id',
        'multi_currency',
        'category',
        'type',
        'name',
        'description',
    ];

    protected $casts = [
        'multi_currency' => 'boolean',
        'category' => AccountCategory::class,
        'type' => AccountType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'subtype_id');
    }

    protected static function newFactory(): Factory
    {
        return AccountSubtypeFactory::new();
    }
}
