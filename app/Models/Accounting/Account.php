<?php

namespace App\Models\Accounting;

use App\Casts\MoneyCast;
use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Accounting\AccountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class Account extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'company_id',
        'subtype_id',
        'parent_id',
        'category',
        'type',
        'code',
        'name',
        'currency_code',
        'starting_balance',
        'debit_balance',
        'credit_balance',
        'net_movement',
        'ending_balance',
        'description',
        'active',
        'default',
        'accountable_id',
        'accountable_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'category' => AccountCategory::class,
        'type' => AccountType::class,
        'starting_balance' => MoneyCast::class,
        'debit_balance' => MoneyCast::class,
        'credit_balance' => MoneyCast::class,
        'ending_balance' => MoneyCast::class,
        'active' => 'boolean',
        'default' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'account_id');
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(AccountSubtype::class, 'subtype_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id')
            ->whereKeyNot($this->getKey());
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
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

    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            JournalEntry::class,
            'account_id',
            'id',
            'id',
            'transaction_id',
        );
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'account_id');
    }

    protected static function newFactory(): Factory
    {
        return AccountFactory::new();
    }
}
