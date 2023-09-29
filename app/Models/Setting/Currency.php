<?php

namespace App\Models\Setting;

use Akaunting\Money\Currency as ISOCurrencies;
use App\Casts\RateCast;
use App\Models\Banking\Account;
use App\Traits\{Blamable, CompanyOwned, SyncsWithCompanyDefaults};
use Database\Factories\Setting\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\{Factory, HasFactory};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Facades\DB;
use Wallo\FilamentCompanies\FilamentCompanies;

class Currency extends Model
{
    use Blamable;
    use CompanyOwned;
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
        'rate' => RateCast::class,
    ];

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public static function getAvailableCurrencyCodes(): array
    {
        $allISOCurrencies = static::getAllCurrencies();
        $allISOCurrencyCodes = array_keys($allISOCurrencies);

        $storedCurrencyCodes = static::query()
            ->pluck('code')
            ->toArray();

        $availableCurrencyCodes = array_diff($allISOCurrencyCodes, $storedCurrencyCodes);

        return array_combine($availableCurrencyCodes, $availableCurrencyCodes);
    }

    public static function getAllCurrencies(): array
    {
        return ISOCurrencies::getCurrencies();
    }

    public static function getDefaultCurrencyCode(): ?string
    {
        $defaultCurrency = static::query()
            ->where('enabled', true)
            ->first();

        return $defaultCurrency?->code ?? null;
    }

    public static function convertBalance($balance, $oldCurrency, $newCurrency): int
    {
        $currencies = self::whereIn('code', [$oldCurrency, $newCurrency])->get();
        $oldCurrency = $currencies->firstWhere('code', $oldCurrency);
        $newCurrency = $currencies->firstWhere('code', $newCurrency);

        $oldRate = DB::table('currencies')
            ->where('code', $oldCurrency->code)
            ->value('rate');

        $newRate = DB::table('currencies')
            ->where('code', $newCurrency->code)
            ->value('rate');

        $precision = max($oldCurrency->precision, $newCurrency->precision);

        $scale = 10 ** $precision;

        $cleanBalance = (int) filter_var($balance, FILTER_SANITIZE_NUMBER_INT);

        return round(($cleanBalance * $newRate * $scale) / ($oldRate * $scale));
    }

    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
