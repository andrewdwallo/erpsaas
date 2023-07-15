<?php

namespace App\Models\Setting;

use App\Models\Document\Document;
use App\Scopes\CurrentCompanyScope;
use Database\Factories\DocumentDefaultFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class DocumentDefault extends Model
{
    use HasFactory;

    protected $table = 'document_defaults';

    protected $fillable = [
        'company_id',
        'type',
        'document_number_prefix',
        'document_number_digits',
        'document_number_next',
        'payment_terms',
        'template',
        'title',
        'subheading',
        'notes',
        'terms',
        'footer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentCompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public static function getDocumentNumberDigits(): array
    {
        return array_combine(range(1, 20), range(1, 20));
    }

    public static function getDefaultDocumentNumberDigits(string $type = 'invoice'): int
    {
        $documentNumberDigits = self::where('type', $type)->pluck('document_number_digits', 'id')->toArray();

        return array_key_first($documentNumberDigits) ?? 5;
    }

    public static function getDefaultDocumentNumberNext(int|null $numDigits = null, string $type = 'invoice'): string
    {
        // Fetch the latest document
        $latestDocument = Document::where('type', $type)->orderBy('id', 'desc')->first();

        // If there are no documents yet, start from 1
        if (!$latestDocument) {
            $nextNumber = 1;
        } else {
            // Otherwise, increment the latest document's number
            $nextNumber = (int)$latestDocument->document_number + 1;
        }

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
        $paymentTerms = self::where('type', $type)->pluck('payment_terms', 'id')->toArray();

        return array_key_first($paymentTerms) ?? 30;
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
