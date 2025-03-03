<?php

namespace App\Models\Banking;

use App\Casts\MoneyCast;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Banking\BankAccountType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectedBankAccount extends Model
{
    use Blamable;
    use CompanyOwned;

    protected $table = 'connected_bank_accounts';

    protected $fillable = [
        'company_id',
        'institution_id',
        'bank_account_id',
        'external_account_id',
        'access_token',
        'identifier',
        'item_id',
        'currency_code',
        'current_balance',
        'name',
        'mask',
        'type',
        'subtype',
        'import_transactions',
        'last_imported_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_balance' => MoneyCast::class,
        'import_transactions' => 'boolean',
        'type' => BankAccountType::class,
        'access_token' => 'encrypted',
        'last_imported_at' => 'datetime',
    ];

    protected $appends = [
        'masked_number',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    protected function maskedNumber(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['mask'] ? '•••• ' . substr($attributes['mask'], -4) : null;
        });
    }
}
