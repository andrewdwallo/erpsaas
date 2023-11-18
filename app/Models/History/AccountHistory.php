<?php

namespace App\Models\History;

use App\Casts\CurrencyRateCast;
use App\Casts\MoneyCast;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class AccountHistory extends Model
{
    use HasFactory;

    protected $table = 'account_histories';

    protected $fillable = [
        'company_id',
        'account_id',
        'type',
        'name',
        'number',
        'currency_code',
        'opening_balance',
        'balance',
        'exchange_rate',
        'status',
        'actions',
        'description',
        'enabled',
        'changed_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'opening_balance' => MoneyCast::class,
        'balance' => MoneyCast::class,
        'exchange_rate' => CurrencyRateCast::class,
        'actions' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'changed_by');
    }
}
