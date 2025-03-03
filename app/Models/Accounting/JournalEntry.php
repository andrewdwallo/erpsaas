<?php

namespace App\Models\Accounting;

use App\Casts\JournalEntryCast;
use App\Collections\Accounting\JournalEntryCollection;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\JournalEntryType;
use Database\Factories\Accounting\JournalEntryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'account_id',
        'transaction_id',
        'type',
        'amount',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => JournalEntryType::class,
        'amount' => JournalEntryCast::class,
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function isUncategorized(): bool
    {
        return $this->account->isUncategorized();
    }

    protected static function newFactory(): Factory
    {
        return JournalEntryFactory::new();
    }

    public function newCollection(array $models = []): JournalEntryCollection
    {
        return new JournalEntryCollection($models);
    }
}
