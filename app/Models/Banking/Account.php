<?php

namespace App\Models\Banking;

use App\Models\Setting\Currency;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Wallo\FilamentCompanies\FilamentCompanies;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'type',
        'name',
        'number',
        'currency_code',
        'opening_balance',
        'enabled',
        'bank_name',
        'bank_phone',
        'bank_address',
        'company_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function owner(): BelongsTo
    {
        return $this->company->owner;
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public static function getAccountTypes(): array
    {
        return [
            'bank' => 'Bank',
            'card' => 'Credit Card',
        ];
    }

    public static function getCurrencyCodes(): array
    {
        $codes = array_keys(Config::get('money'));

        return array_combine($codes, $codes);
    }

    public static function getDefaultCurrencyCode(): ?string
    {
        $defaultCurrency = Currency::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->first();

        return $defaultCurrency?->code;
    }

    protected static function newFactory(): Factory
    {
        return AccountFactory::new();
    }
}
