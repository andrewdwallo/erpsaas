<?php

namespace App\Models\Setting;

use App\Models\Document\Document;
use App\Scopes\CurrentCompanyScope;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\DocumentDefaultFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class DocumentDefault extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'document_defaults';

    protected $fillable = [
        'type',
        'document_logo',
        'number_prefix',
        'number_digits',
        'number_next',
        'payment_terms',
        'title',
        'subheading',
        'terms',
        'footer',
        'accent_color',
        'template',
        'item_column',
        'unit_column',
        'price_column',
        'amount_column',
        'created_by',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public static function getAvailableNumberDigits(): array
    {
        return array_combine(range(1, 20), range(1, 20));
    }

    public static function getDefaultNumberDigits(string $type = 'invoice'): int
    {
        return static::where('type', $type)->value('number_digits') ?? 5;
    }

    public static function getNextDocumentNumber(int|null $numDigits = null, string $type = 'invoice'): string
    {
        $latestDocument = Document::where('type', $type)->orderBy('id', 'desc')->first();
        $nextNumber = $latestDocument ? ((int)$latestDocument->number + 1) : 1;
        return str_pad($nextNumber, $numDigits, '0', STR_PAD_LEFT);
    }

    public static function getPaymentTerms(): array
    {
        return [
            0 => 'Due on Receipt',
            7 => 'Net 7',
            10 => 'Net 10',
            15 => 'Net 15',
            30 => 'Net 30',
            60 => 'Net 60',
            90 => 'Net 90',
        ];
    }

    public static function getDefaultPaymentTerms(string $type = 'invoice'): int
    {
        return static::where('type', $type)->value('payment_terms') ?? 30;
    }

    public static function getItemColumns(): array
    {
        return [
            'items' => 'Items',
            'products' => 'Products',
            'services' => 'Services',
            'other' => 'Other',
        ];
    }

    public static function getDefaultItemColumn(string $type = 'invoice'): string
    {
        return static::where('type', $type)->value('item_column') ?? 'items';
    }

    public static function getUnitColumns(): array
    {
        return [
            'quantity' => 'Quantity',
            'hours' => 'Hours',
            'other' => 'Other',
        ];
    }

    public static function getDefaultUnitColumn(string $type = 'invoice'): string
    {
        return static::where('type', $type)->value('unit_column') ?? 'quantity';
    }

    public static function getPriceColumns(): array
    {
        return [
            'price' => 'Price',
            'rate' => 'Rate',
            'other' => 'Other',
        ];
    }

    public static function getDefaultPriceColumn(string $type = 'invoice'): string
    {
        return static::where('type', $type)->value('price_column') ?? 'price';
    }

    public static function getAmountColumns(): array
    {
        return [
            'amount' => 'Amount',
            'total' => 'Total',
            'other' => 'Other',
        ];
    }

    public static function getDefaultAmountColumn(string $type = 'invoice'): string
    {
        return static::where('type', $type)->value('amount_column') ?? 'amount';
    }

    public function getDocumentNumberAttribute(): string
    {
        return $this->document_number_prefix . str_pad($this->document_number_next, $this->document_number_digits, '0', STR_PAD_LEFT);
    }

    protected static function newFactory(): Factory
    {
        return DocumentDefaultFactory::new();
    }
}
