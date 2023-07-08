<?php

namespace App\Models\Setting;

use App\Models\Document\DocumentItem;
use App\Models\Item;
use App\Models\User;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class Discount extends Model
{
    use HasFactory;

    protected $table = 'discounts';

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'rate',
        'computation',
        'type',
        'scope',
        'start_date',
        'end_date',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function document_items(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    public function bill_items(): HasMany
    {
        return $this->document_items()->where('type', 'bill');
    }

    public function invoice_items(): HasMany
    {
        return $this->document_items()->where('type', 'invoice');
    }

    public static function getComputationTypes(): array
    {
        return [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed',
        ];
    }

    public static function getDiscountTypes(): array
    {
        return [
            'sales' => 'Sales',
            'purchase' => 'Purchase',
            'none' => 'None',
        ];
    }

    public static function getDiscountScopes(): array
    {
        return [
            'product' => 'Product',
            'service' => 'Service',
        ];
    }

    protected static function newFactory(): Factory
    {
        return DiscountFactory::new();
    }
}
