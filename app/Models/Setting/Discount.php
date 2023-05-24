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

class Discount extends Model
{
    use HasFactory;

    protected $table = 'discounts';

    protected $fillable = [
        'name',
        'description',
        'rate',
        'computation',
        'type',
        'scope',
        'enabled',
        'created_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    protected static function newFactory(): Factory
    {
        return DiscountFactory::new();
    }
}
