<?php

namespace App\Models\Setting;

use App\Casts\CurrencyRateCast;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Concerns\HasDefault;
use App\Concerns\SyncsWithCompanyDefaults;
use App\Facades\Forex;
use App\Models\Accounting\Account;
use App\Observers\CurrencyObserver;
use App\Utilities\Currency\CurrencyAccessor;
use Database\Factories\Setting\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(CurrencyObserver::class)]
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

    public function defaultCurrency(): HasOne
    {
        return $this->hasOne(CompanyDefault::class, 'currency_code', 'code');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'currency_code', 'code');
    }

    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
