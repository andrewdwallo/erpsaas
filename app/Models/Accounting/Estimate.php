<?php

namespace App\Models\Accounting;

use App\Casts\MoneyCast;
use App\Casts\RateCast;
use App\Collections\Accounting\DocumentCollection;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\EstimateStatus;
use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Company\Resources\Sales\EstimateResource;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Models\Common\Client;
use App\Models\Company;
use App\Models\Setting\DocumentDefault;
use App\Observers\EstimateObserver;
use Filament\Actions\Action;
use Filament\Actions\MountableAction;
use Filament\Actions\ReplicateAction;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[CollectedBy(DocumentCollection::class)]
#[ObservedBy(EstimateObserver::class)]
class Estimate extends Document
{
    protected $fillable = [
        'company_id',
        'client_id',
        'logo',
        'header',
        'subheader',
        'estimate_number',
        'reference_number',
        'date',
        'expiration_date',
        'approved_at',
        'accepted_at',
        'converted_at',
        'declined_at',
        'last_sent_at',
        'last_viewed_at',
        'status',
        'currency_code',
        'discount_method',
        'discount_computation',
        'discount_rate',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'terms',
        'footer',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'expiration_date' => 'date',
        'approved_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'status' => EstimateStatus::class,
        'discount_method' => DocumentDiscountMethod::class,
        'discount_computation' => AdjustmentComputation::class,
        'discount_rate' => RateCast::class,
        'subtotal' => MoneyCast::class,
        'tax_total' => MoneyCast::class,
        'discount_total' => MoneyCast::class,
        'total' => MoneyCast::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public static function documentType(): DocumentType
    {
        return DocumentType::Estimate;
    }

    public function documentNumber(): ?string
    {
        return $this->estimate_number;
    }

    public function documentDate(): ?string
    {
        return $this->date?->toDateString();
    }

    public function dueDate(): ?string
    {
        return $this->expiration_date?->toDateString();
    }

    public function referenceNumber(): ?string
    {
        return $this->reference_number;
    }

    public function amountDue(): ?string
    {
        return $this->total;
    }

    protected function isCurrentlyExpired(): Attribute
    {
        return Attribute::get(function () {
            return $this->expiration_date?->isBefore(today()) && $this->canBeExpired();
        });
    }

    public function isDraft(): bool
    {
        return $this->status === EstimateStatus::Draft;
    }

    public function wasApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function wasAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function wasDeclined(): bool
    {
        return $this->declined_at !== null;
    }

    public function wasConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function hasBeenSent(): bool
    {
        return $this->last_sent_at !== null;
    }

    public function hasBeenViewed(): bool
    {
        return $this->last_viewed_at !== null;
    }

    public function canBeExpired(): bool
    {
        return ! in_array($this->status, [
            EstimateStatus::Draft,
            EstimateStatus::Accepted,
            EstimateStatus::Declined,
            EstimateStatus::Converted,
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && ! $this->wasApproved();
    }

    public function canBeConverted(): bool
    {
        return $this->wasAccepted() && ! $this->wasConverted();
    }

    public function canBeMarkedAsDeclined(): bool
    {
        return $this->hasBeenSent()
            && ! $this->wasDeclined()
            && ! $this->wasConverted()
            && ! $this->wasAccepted();
    }

    public function canBeMarkedAsSent(): bool
    {
        return ! $this->hasBeenSent();
    }

    public function canBeMarkedAsAccepted(): bool
    {
        return $this->hasBeenSent()
            && ! $this->wasAccepted()
            && ! $this->wasDeclined()
            && ! $this->wasConverted();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            EstimateStatus::Unsent,
            EstimateStatus::Sent,
            EstimateStatus::Viewed,
            EstimateStatus::Accepted,
        ]);
    }

    public static function getNextDocumentNumber(?Company $company = null): string
    {
        $company ??= auth()->user()?->currentCompany;

        if (! $company) {
            throw new \RuntimeException('No current company is set for the user.');
        }

        $defaultEstimateSettings = $company->defaultEstimate;

        $numberPrefix = $defaultEstimateSettings->number_prefix ?? '';

        $latestDocument = static::query()
            ->whereNotNull('estimate_number')
            ->latest('estimate_number')
            ->first();

        $lastNumberNumericPart = $latestDocument
            ? (int) substr($latestDocument->estimate_number, strlen($numberPrefix))
            : DocumentDefault::getBaseNumber();

        $numberNext = $lastNumberNumericPart + 1;

        return $defaultEstimateSettings->getNumberNext(
            prefix: $numberPrefix,
            next: $numberNext
        );
    }

    public function approveDraft(?Carbon $approvedAt = null): void
    {
        if (! $this->isDraft()) {
            throw new \RuntimeException('Estimate is not in draft status.');
        }

        $approvedAt ??= now();

        $this->update([
            'approved_at' => $approvedAt,
            'status' => EstimateStatus::Unsent,
        ]);
    }

    public static function getApproveDraftAction(string $action = Action::class): MountableAction
    {
        return $action::make('approveDraft')
            ->label('Approve')
            ->icon('heroicon-m-check-circle')
            ->visible(function (self $record) {
                return $record->canBeApproved();
            })
            ->databaseTransaction()
            ->successNotificationTitle('Estimate approved')
            ->action(function (self $record, MountableAction $action) {
                $record->approveDraft();

                $action->success();
            });
    }

    public static function getMarkAsSentAction(string $action = Action::class): MountableAction
    {
        return $action::make('markAsSent')
            ->label('Mark as sent')
            ->icon('heroicon-m-paper-airplane')
            ->visible(static function (self $record) {
                return $record->canBeMarkedAsSent();
            })
            ->successNotificationTitle('Estimate sent')
            ->action(function (self $record, MountableAction $action) {
                $record->markAsSent();

                $action->success();
            });
    }

    public function markAsSent(?Carbon $sentAt = null): void
    {
        $sentAt ??= now();

        $this->update([
            'status' => EstimateStatus::Sent,
            'last_sent_at' => $sentAt,
        ]);
    }

    public function markAsViewed(?Carbon $viewedAt = null): void
    {
        $viewedAt ??= now();

        $this->update([
            'status' => EstimateStatus::Viewed,
            'last_viewed_at' => $viewedAt,
        ]);
    }

    public static function getReplicateAction(string $action = ReplicateAction::class): MountableAction
    {
        return $action::make()
            ->excludeAttributes([
                'estimate_number',
                'date',
                'expiration_date',
                'approved_at',
                'accepted_at',
                'converted_at',
                'declined_at',
                'last_sent_at',
                'last_viewed_at',
                'status',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ])
            ->modal(false)
            ->beforeReplicaSaved(function (self $original, self $replica) {
                $replica->status = EstimateStatus::Draft;
                $replica->estimate_number = self::getNextDocumentNumber();
                $replica->date = now();
                $replica->expiration_date = now()->addDays($original->company->defaultInvoice->payment_terms->getDays());
            })
            ->databaseTransaction()
            ->after(function (self $original, self $replica) {
                $original->replicateLineItems($replica);
            })
            ->successRedirectUrl(static function (self $replica) {
                return EstimateResource::getUrl('edit', ['record' => $replica]);
            });
    }

    public static function getMarkAsAcceptedAction(string $action = Action::class): MountableAction
    {
        return $action::make('markAsAccepted')
            ->label('Mark as Accepted')
            ->icon('heroicon-m-check-badge')
            ->visible(static function (self $record) {
                return $record->canBeMarkedAsAccepted();
            })
            ->databaseTransaction()
            ->successNotificationTitle('Estimate accepted')
            ->action(function (self $record, MountableAction $action) {
                $record->markAsAccepted();

                $action->success();
            });
    }

    public function markAsAccepted(?Carbon $acceptedAt = null): void
    {
        $acceptedAt ??= now();

        $this->update([
            'status' => EstimateStatus::Accepted,
            'accepted_at' => $acceptedAt,
        ]);
    }

    public static function getMarkAsDeclinedAction(string $action = Action::class): MountableAction
    {
        return $action::make('markAsDeclined')
            ->label('Mark as Declined')
            ->icon('heroicon-m-x-circle')
            ->visible(static function (self $record) {
                return $record->canBeMarkedAsDeclined();
            })
            ->color('danger')
            ->requiresConfirmation()
            ->databaseTransaction()
            ->successNotificationTitle('Estimate declined')
            ->action(function (self $record, MountableAction $action) {
                $record->markAsDeclined();

                $action->success();
            });
    }

    public function markAsDeclined(?Carbon $declinedAt = null): void
    {
        $declinedAt ??= now();

        $this->update([
            'status' => EstimateStatus::Declined,
            'declined_at' => $declinedAt,
        ]);
    }

    public static function getConvertToInvoiceAction(string $action = Action::class): MountableAction
    {
        return $action::make('convertToInvoice')
            ->label('Convert to Invoice')
            ->icon('heroicon-m-arrow-right-on-rectangle')
            ->visible(static function (self $record) {
                return $record->canBeConverted();
            })
            ->databaseTransaction()
            ->successNotificationTitle('Estimate converted to invoice')
            ->action(function (self $record, MountableAction $action) {
                $record->convertToInvoice();

                $action->success();
            })
            ->successRedirectUrl(static function (self $record) {
                return InvoiceResource::getUrl('edit', ['record' => $record->refresh()->invoice]);
            });
    }

    public function convertToInvoice(?Carbon $convertedAt = null): void
    {
        if ($this->invoice) {
            throw new \RuntimeException('Estimate has already been converted to an invoice.');
        }

        $invoice = $this->invoice()->create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'logo' => $this->logo,
            'header' => $this->company->defaultInvoice->header,
            'subheader' => $this->company->defaultInvoice->subheader,
            'invoice_number' => Invoice::getNextDocumentNumber($this->company),
            'date' => now(),
            'due_date' => now()->addDays($this->company->defaultInvoice->payment_terms->getDays()),
            'status' => InvoiceStatus::Draft,
            'currency_code' => $this->currency_code,
            'discount_method' => $this->discount_method,
            'discount_computation' => $this->discount_computation,
            'discount_rate' => $this->discount_rate,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'total' => $this->total,
            'terms' => $this->terms,
            'footer' => $this->footer,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->replicateLineItems($invoice);

        $convertedAt ??= now();

        $this->update([
            'status' => EstimateStatus::Converted,
            'converted_at' => $convertedAt,
        ]);
    }

    public function replicateLineItems(Model $target): void
    {
        $this->lineItems->each(function (DocumentLineItem $lineItem) use ($target) {
            $replica = $lineItem->replicate([
                'documentable_id',
                'documentable_type',
                'subtotal',
                'total',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]);

            $replica->documentable_id = $target->id;
            $replica->documentable_type = $target->getMorphClass();
            $replica->save();

            $replica->adjustments()->sync($lineItem->adjustments->pluck('id'));
        });
    }
}
