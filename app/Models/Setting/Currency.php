<?php

namespace App\Models\Setting;

use App\Models\Banking\Account;
use App\Scopes\CurrentCompanyScope;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;
use Wallo\FilamentCompanies\FilamentCompanies;

class Currency extends Model
{
    use HasFactory;

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
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function defaultCurrency(): HasOne
    {
        return $this->hasOne(DefaultSetting::class, 'currency_code', 'code');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'currency_code', 'code');
    }

    public static function getCurrencyCodes(): array
    {
        $allCodes = array_keys(Config::get('money'));

        $storedCodes = static::query()
            ->pluck('code')
            ->toArray();

        $codes = array_diff($allCodes, $storedCodes);

        return array_combine($codes, $codes);
    }

    public static function getDefaultCurrency(): ?string
    {
        $defaultCurrency = self::where('enabled', true)
            ->first();

        return $defaultCurrency->code ?? null;
    }

    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
