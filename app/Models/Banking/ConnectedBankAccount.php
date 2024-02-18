<?php

namespace App\Models\Banking;

use App\Enums\BankAccountType;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

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
        'name',
        'mask',
        'type',
        'subtype',
        'import_transactions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'import_transactions' => 'boolean',
        'type' => BankAccountType::class,
        'access_token' => 'encrypted',
    ];

    protected $appends = [
        'masked_number',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }
}
