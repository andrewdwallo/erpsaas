<?php

namespace App\Models\Banking;

use App\Casts\MoneyCast;
use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Models\History\AccountHistory;
use App\Models\Setting\Currency;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use App\Traits\HasDefault;
use App\Traits\SyncsWithCompanyDefaults;
use Database\Factories\Banking\AccountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Tags\HasTags;
use Wallo\FilamentCompanies\FilamentCompanies;

class Account extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasDefault;
    use HasFactory;
    use HasTags;
    use SyncsWithCompanyDefaults;

    protected $table = 'accounts';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'number',
        'currency_code',
        'opening_balance',
        'balance',
        'description',
        'notes',
        'status',
        'bank_name',
        'bank_phone',
        'bank_address',
        'bank_website',
        'bic_swift_code',
        'iban',
        'aba_routing_number',
        'ach_routing_number',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => AccountType::class,
        'status' => AccountStatus::class,
        'enabled' => 'boolean',
        'opening_balance' => MoneyCast::class,
        'balance' => MoneyCast::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AccountHistory::class, 'account_id');
    }

    protected static function newFactory(): Factory
    {
        return AccountFactory::new();
    }
}
