<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\DocumentType;
use App\Models\Setting\Currency;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Livewire\Component;

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
        return $this->morphMany(DocumentLineItem::class, 'documentable')->orderBy('line_number');
    }

    public function hasLineItems(): bool
    {
        return $this->lineItems()->exists();
    }

    public function hasInactiveAdjustments(): bool
    {
        return $this->lineItems->contains(function (DocumentLineItem $lineItem) {
            return $lineItem->adjustments->contains(function (Adjustment $adjustment) {
                return $adjustment->isInactive();
            });
        });
    }

    public static function getPrintDocumentAction(string $action = Action::class): Action
    {
        return $action::make('printPdf')
            ->label('Print')
            ->icon('heroicon-m-printer')
            ->action(function (self $record, Component $livewire) {
                $url = route('documents.print', [
                    'documentType' => $record::documentType(),
                    'id' => $record->id,
                ]);

                $livewire->js("window.printPdf('{$url}')");
            });
    }

    abstract public static function documentType(): DocumentType;

    abstract public function documentNumber(): ?string;

    abstract public function documentDate(): ?string;

    abstract public function dueDate(): ?string;

    abstract public function referenceNumber(): ?string;

    abstract public function amountDue(): ?string;
}
