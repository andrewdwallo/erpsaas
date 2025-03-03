<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\DocumentType;
use App\Models\Setting\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

abstract class Document extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function lineItems(): MorphMany
    {
        return $this->morphMany(DocumentLineItem::class, 'documentable');
    }

    public function hasLineItems(): bool
    {
        return $this->lineItems()->exists();
    }

    abstract public static function documentType(): DocumentType;

    abstract public function documentNumber(): ?string;

    abstract public function documentDate(): ?string;

    abstract public function dueDate(): ?string;

    abstract public function referenceNumber(): ?string;

    abstract public function amountDue(): ?string;
}
