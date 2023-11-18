<?php

namespace App\Models\Setting;

use App\Casts\CurrencyRateCast;
use App\Facades\Forex;
use App\Models\Banking\Account;
use App\Models\History\AccountHistory;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use App\Traits\HasDefault;
use App\Traits\SyncsWithCompanyDefaults;
use App\Utilities\Currency\CurrencyAccessor;
use Database\Factories\Setting\CurrencyFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Currency extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasDefault;
    use HasFactory;
    use SyncsWithCompanyDefaults;

    protected $table = 'currencies';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'rate',
        'precision',
        'symbol',
        'symbol_first',
        'decimal_mark',
        'thousands_separator',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'symbol_first' => 'boolean',
        'rate' => CurrencyRateCast::class,
    ];

    protected $appends = ['live_rate'];

    protected function liveRate(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?float {
            $baseCurrency = CurrencyAccessor::getDefaultCurrency();
            $targetCurrency = $attributes['code'];

            if ($baseCurrency === $targetCurrency) {
                return 1;
            }

            $exchangeRate = Forex::getCachedExchangeRate($baseCurrency, $targetCurrency);

            return $exchangeRate ?? null;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function defaultCurrency(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'currency_code', 'code');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'currency_code', 'code');
    }

    public function accountHistories(): HasMany
    {
        return $this->hasMany(AccountHistory::class, 'currency_code', 'code');
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
        return CurrencyFactory::new();
    }
}
