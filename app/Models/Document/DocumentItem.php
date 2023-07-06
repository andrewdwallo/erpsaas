<?php

namespace App\Models\Document;

use App\Models\Item;
use App\Models\Setting\Discount;
use App\Models\Setting\Tax;
use Database\Factories\DocumentItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class DocumentItem extends Model
{
    use HasFactory;

    protected $table = 'document_items';

    protected $fillable = [
        'company_id',
        'document_id',
        'item_id',
        'type',
        'name',
        'description',
        'quantity',
        'price',
        'tax_id',
        'discount_id',
        'total',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function scopeBill($query)
    {
        return $query->where('type', 'bill');
    }

    public function scopeInvoice($query)
    {
        return $query->where('type', 'invoice');
    }

    /**
     * Calculate and return the net price (price - discount + tax)
     */
    public function getNetPriceAttribute()
    {
        $discountAmount = $this->discount ? ($this->price * $this->discount->rate / 100) : 0;
        $taxAmount = $this->tax ? ($this->price * $this->tax->rate / 100) : 0;

        return $this->price - $discountAmount + $taxAmount;
    }

    protected static function newFactory(): Factory
    {
        return DocumentItemFactory::new();
    }
}
