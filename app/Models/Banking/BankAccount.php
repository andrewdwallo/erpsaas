<?php

namespace App\Models\Banking;

use App\Enums\BankAccountType;
use App\Models\Accounting\Account;
use App\Observers\BankAccountObserver;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use App\Traits\HasDefault;
use App\Traits\SyncsWithCompanyDefaults;
use Database\Factories\Banking\BankAccountFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Wallo\FilamentCompanies\FilamentCompanies;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function connectedBankAccount(): HasOne
    {
        return $this->hasOne(ConnectedBankAccount::class, 'bank_account_id');
    }

    public function account(): MorphOne
    {
        return $this->morphOne(Account::class, 'accountable');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
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
