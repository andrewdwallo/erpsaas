<?php

namespace App\Models\Banking;

use App\Models\Setting\Currency;
use App\Models\Setting\DefaultSetting;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\Tags\HasTags;
use Wallo\FilamentCompanies\FilamentCompanies;

class Account extends Model
{
    use HasFactory;
    use HasTags;

    protected $table = 'accounts';

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'number',
        'currency_code',
        'opening_balance',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function default_settings(): HasMany
    {
        return $this->hasMany(DefaultSetting::class, 'account_id', 'id');
    }

    public static function getAccountTypes(): array
    {
        return [
            'checking' => 'Checking',
            'savings' => 'Savings',
            'money_market' => 'Money Market',
            'certificate_of_deposit' => 'Certificate of Deposit',
            'credit_card' => 'Credit Card',
        ];
    }

    public static function getAccountStatuses(): array
    {
        return [
            'open' => 'Open',
            'active' => 'Active',
            'dormant' => 'Dormant',
            'restricted' => 'Restricted',
            'closed' => 'Closed',
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
