<?php

namespace App\Models\Setting;

use App\Models\Banking\Account;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Wallo\FilamentCompanies\FilamentCompanies;

class Currency extends Model
{
    use HasFactory;

    protected $table = 'currencies';

    protected $fillable = [
        'name',
        'code',
        'rate',
        'precision',
        'symbol',
        'symbol_first',
        'decimal_mark',
        'thousands_separator',
        'enabled',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'symbol_first' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'currency_code', 'code');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public static function getCurrencyCodes(): array
    {
        $allCodes = array_keys(Config::get('money'));

        $storedCodes = static::query()
            ->where('company_id', Auth::user()->currentCompany->id)
            ->pluck('code')
            ->toArray();

        $codes = array_diff($allCodes, $storedCodes);

        return array_combine($codes, $codes);
    }

    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
