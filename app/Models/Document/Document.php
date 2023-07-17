<?php

namespace App\Models\Document;

use App\Models\Contact;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\DocumentDefault;
use App\Models\Setting\Tax;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Document extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'documents';

    protected $fillable = [
        'company_id',
        'document_default_id',
        'type',
        'document_number',
        'order_number',
        'status',
        'document_date',
        'due_date',
        'paid_date',
        'amount',
        'tax_id',
        'discount_id',
        'reference',
        'currency_code',
        'category_id',
        'contact_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_date' => 'datetime',
        'due_date' => 'datetime',
        'paid_date' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function documentDefault(): BelongsTo
    {
        return $this->belongsTo(DocumentDefault::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function total(): HasOne
    {
        return $this->hasOne(DocumentTotal::class);
    }

    protected static function newFactory(): Factory
    {
        return DocumentFactory::new();
    }
}
