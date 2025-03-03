<?php

namespace App\Models\Accounting;

use App\Casts\TransactionAmountCast;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\PaymentMethod;
use App\Enums\Accounting\TransactionType;
use App\Models\Banking\BankAccount;
use App\Models\Common\Contact;
use App\Observers\TransactionObserver;
use Database\Factories\Accounting\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account_id', // Account from Chart of Accounts (Income/Expense accounts)
        'bank_account_id', // Cash/Bank Account
        'plaid_transaction_id',
        'contact_id',
        'type', // 'deposit', 'withdrawal', 'journal'
        'payment_channel',
        'payment_method',
        'is_payment',
        'description',
        'notes',
        'reference',
        'amount',
        'pending',
        'reviewed',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'payment_method' => PaymentMethod::class,
        'amount' => TransactionAmountCast::class,
        'pending' => 'boolean',
        'reviewed' => 'boolean',
        'posted_at' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'transaction_id');
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUncategorized(): bool
    {
        return $this->journalEntries->contains(fn (JournalEntry $entry) => $entry->account->isUncategorized());
    }

    public function updateAmountIfBalanced(): void
    {
        if ($this->journalEntries->areBalanced() && $this->journalEntries->sumDebits()->formatSimple() !== $this->getAttributeValue('amount')) {
            $this->setAttribute('amount', $this->journalEntries->sumDebits()->formatSimple());
            $this->save();
        }
    }

    protected static function newFactory(): Factory
    {
        return TransactionFactory::new();
    }
}
