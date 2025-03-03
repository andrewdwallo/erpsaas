<?php

namespace App\Models\Banking;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Concerns\HasDefault;
use App\Concerns\SyncsWithCompanyDefaults;
use App\Enums\Banking\BankAccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Observers\BankAccountObserver;
use Database\Factories\Banking\BankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(BankAccountObserver::class)]
class BankAccount extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasDefault;
    use HasFactory;
    use SyncsWithCompanyDefaults;

    protected $table = 'bank_accounts';

    protected $fillable = [
        'company_id',
        'account_id',
        'institution_id',
        'type',
        'number',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => BankAccountType::class,
        'enabled' => 'boolean',
    ];

    protected $appends = [
        'mask',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function connectedBankAccount(): HasOne
    {
        return $this->hasOne(ConnectedBankAccount::class, 'bank_account_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'bank_account_id');
    }

    protected function mask(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['number'] ? '•••• ' . substr($attributes['number'], -4) : null;
        });
    }

    protected static function newFactory(): Factory
    {
        return BankAccountFactory::new();
    }
}
