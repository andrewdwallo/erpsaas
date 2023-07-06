<?php

namespace App\Models\Document;

use Database\Factories\DocumentTotalFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class DocumentTotal extends Model
{
    use HasFactory;

    protected $table = 'document_totals';

    protected $fillable = [
        'company_id',
        'document_id',
        'type',
        'code',
        'name',
        'subtotal',
        'discount',
        'tax',
        'total',
        'created_by',
        'updated_by',
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

    public function scopeInvoice($query)
    {
        return $query->where('type', 'invoice');
    }

    public function scopeBill($query)
    {
        return $query->where('type', 'bill');
    }

    protected static function newFactory(): Factory
    {
        return DocumentTotalFactory::new();
    }
}
