<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Facades\Accounting;
use App\Models\Banking\BankAccount;
use App\Models\Setting\Currency;
use App\Observers\AccountObserver;
use Database\Factories\Accounting\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[ObservedBy(AccountObserver::class)]
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
        'description',
        'archived',
        'default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'category' => AccountCategory::class,
        'type' => AccountType::class,
        'archived' => 'boolean',
        'default' => 'boolean',
    ];

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

    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class, 'account_id');
    }

    public function adjustment(): HasOne
    {
        return $this->hasOne(Adjustment::class, 'account_id');
    }

    public function getLastTransactionDate(): ?string
    {
        $lastJournalEntryTransaction = $this->journalEntries()
            ->join('transactions', 'journal_entries.transaction_id', '=', 'transactions.id')
            ->max('transactions.posted_at');

        if ($lastJournalEntryTransaction) {
            return Carbon::parse($lastJournalEntryTransaction)->format('F j, Y');
        }

        return null;
    }

    protected function endingBalance(): Attribute
    {
        return Attribute::get(function () {
            $company = $this->company;
            $fiscalYearStart = $company->locale->fiscalYearStartDate();
            $fiscalYearEnd = $company->locale->fiscalYearEndDate();

            return Accounting::getEndingBalance($this, $fiscalYearStart, $fiscalYearEnd);
        });
    }

    public function isUncategorized(): bool
    {
        return $this->type->isUncategorized();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'account_id');
    }

    public static function getAccountsReceivableAccount(): self
    {
        return self::where('name', 'Accounts Receivable')->firstOrFail();
    }

    public static function getAccountsPayableAccount(): self
    {
        return self::where('name', 'Accounts Payable')->firstOrFail();
    }

    public static function getSalesDiscountAccount(): self
    {
        return self::where('name', 'Sales Discount')->firstOrFail();
    }

    public static function getPurchaseDiscountAccount(): self
    {
        return self::where('name', 'Purchase Discount')->firstOrFail();
    }

    protected static function newFactory(): Factory
    {
        return AccountFactory::new();
    }
}
