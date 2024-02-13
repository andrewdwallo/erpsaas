<?php

namespace App\Models\Accounting;

use App\Casts\MoneyCast;
use App\Models\Common\Contact;
use App\Models\Setting\Category;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Accounting\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class Transaction extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category_id',
        'contact_id',
        'type',
        'method',
        'payment_channel',
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
        'amount' => MoneyCast::class,
        'pending' => 'boolean',
        'reviewed' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    protected static function newFactory(): Factory
    {
        return TransactionFactory::new();
    }
}
