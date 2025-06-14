<?php

namespace App\Models\Accounting;

use App\Casts\RateCast;
use App\Collections\Accounting\DocumentCollection;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\EditInvoice;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ViewInvoice;
use App\Models\Banking\BankAccount;
use App\Models\Common\Client;
use App\Models\Company;
use App\Models\Setting\DocumentDefault;
use App\Observers\InvoiceObserver;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ReplicateAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use RuntimeException;

#[CollectedBy(DocumentCollection::class)]
#[ObservedBy(InvoiceObserver::class)]
class Invoice extends Document
{
    protected $table = 'invoices';

    protected $fillable = [
        'company_id',
        'client_id',
        'estimate_id',
        'recurring_invoice_id',
        'logo',
        'header',
        'subheader',
        'invoice_number',
        'order_number',
        'date',
        'due_date',
        'approved_at',
        'paid_at',
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
        'amount_paid',
        'terms',
        'footer',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'status' => InvoiceStatus::class,
        'discount_method' => DocumentDiscountMethod::class,
        'discount_computation' => AdjustmentComputation::class,
        'discount_rate' => RateCast::class,
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function logoUrl(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['logo'] ? Storage::disk('public')->url($attributes['logo']) : null;
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function payments(): MorphMany
    {
        return $this->transactions()->where('is_payment', true);
    }

    public function deposits(): MorphMany
    {
        return $this->transactions()->where('type', TransactionType::Deposit)->where('is_payment', true);
    }

    public function withdrawals(): MorphMany
    {
        return $this->transactions()->where('type', TransactionType::Withdrawal)->where('is_payment', true);
    }

    public function approvalTransaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable')
            ->where('type', TransactionType::Journal);
    }

    protected function sourceType(): Attribute
    {
        return Attribute::get(function () {
            return match (true) {
                $this->estimate_id !== null => DocumentType::Estimate,
                $this->recurring_invoice_id !== null => DocumentType::RecurringInvoice,
                default => null,
            };
        });
    }

    public static function documentType(): DocumentType
    {
        return DocumentType::Invoice;
    }

    public function documentNumber(): ?string
    {
        return $this->invoice_number;
    }

    public function documentDate(): ?string
    {
        return $this->date?->toDefaultDateFormat();
    }

    public function dueDate(): ?string
    {
        return $this->due_date?->toDefaultDateFormat();
    }

    public function referenceNumber(): ?string
    {
        return $this->order_number;
    }

    public function amountDue(): ?string
    {
        return $this->amount_due;
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::unpaidStatuses());
    }

    // TODO: Consider storing the numeric part of the invoice number separately
    public function scopeByNumber(Builder $query, string $number): Builder
    {
        $invoicePrefix = DocumentDefault::invoice()->first()->number_prefix ?? '';

        return $query->where(function ($q) use ($number, $invoicePrefix) {
            $q->where('invoice_number', $number)
                ->orWhere('invoice_number', $invoicePrefix . $number);
        });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->unpaid()
            ->where('status', InvoiceStatus::Overdue);
    }

    public function shouldBeOverdue(): bool
    {
        return $this->due_date->isBefore(today()) && $this->canBeOverdue();
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function wasApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function hasBeenSent(): bool
    {
        return $this->last_sent_at !== null;
    }

    public function hasBeenViewed(): bool
    {
        return $this->last_viewed_at !== null;
    }

    public function canRecordPayment(): bool
    {
        if (! $this->client_id) {
            return false;
        }

        return ! in_array($this->status, [
            InvoiceStatus::Draft,
            InvoiceStatus::Paid,
            InvoiceStatus::Void,
        ]);
    }

    public function canBulkRecordPayment(): bool
    {
        if (! $this->client_id || $this->currency_code !== CurrencyAccessor::getDefaultCurrency()) {
            return false;
        }

        return ! in_array($this->status, [
            InvoiceStatus::Draft,
            InvoiceStatus::Paid,
            InvoiceStatus::Void,
            InvoiceStatus::Overpaid,
        ]);
    }

    public function canBeOverdue(): bool
    {
        return in_array($this->status, InvoiceStatus::canBeOverdue());
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && ! $this->wasApproved();
    }

    public function canBeMarkedAsSent(): bool
    {
        return ! $this->hasBeenSent();
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public static function getNextDocumentNumber(?Company $company = null): string
    {
        $company ??= auth()->user()?->currentCompany;

        if (! $company) {
            throw new RuntimeException('No current company is set for the user.');
        }

        $defaultInvoiceSettings = $company->defaultInvoice;

        $numberPrefix = $defaultInvoiceSettings->number_prefix ?? '';

        $latestDocument = static::query()
            ->whereNotNull('invoice_number')
            ->latest('invoice_number')
            ->first();

        $lastNumberNumericPart = $latestDocument
            ? (int) substr($latestDocument->invoice_number, strlen($numberPrefix))
            : DocumentDefault::getBaseNumber();

        $numberNext = $lastNumberNumericPart + 1;

        return $defaultInvoiceSettings->getNumberNext(
            prefix: $numberPrefix,
            next: $numberNext
        );
    }

    public function recordPayment(array $data): void
    {
        $isRefund = $this->status === InvoiceStatus::Overpaid;

        if ($isRefund) {
            $transactionType = TransactionType::Withdrawal;
            $transactionDescription = "Invoice #{$this->invoice_number}: Refund to {$this->client->name}";
        } else {
            $transactionType = TransactionType::Deposit;
            $transactionDescription = "Invoice #{$this->invoice_number}: Payment from {$this->client->name}";
        }

        $bankAccount = BankAccount::findOrFail($data['bank_account_id']);
        $bankAccountCurrency = $bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency();

        $invoiceCurrency = $this->currency_code;
        $requiresConversion = $invoiceCurrency !== $bankAccountCurrency;

        // Store the original payment amount in invoice currency before any conversion
        $amountInInvoiceCurrencyCents = $data['amount'];

        if ($requiresConversion) {
            $amountInBankCurrencyCents = CurrencyConverter::convertBalance(
                $amountInInvoiceCurrencyCents,
                $invoiceCurrency,
                $bankAccountCurrency
            );
            $formattedAmountForBankCurrency = $amountInBankCurrencyCents;
        } else {
            $formattedAmountForBankCurrency = $amountInInvoiceCurrencyCents;
        }

        // Create transaction
        $this->transactions()->create([
            'company_id' => $this->company_id,
            'type' => $transactionType,
            'is_payment' => true,
            'posted_at' => $data['posted_at'],
            'amount' => $formattedAmountForBankCurrency,
            'payment_method' => $data['payment_method'],
            'bank_account_id' => $data['bank_account_id'],
            'account_id' => Account::getAccountsReceivableAccount($this->company_id)->id,
            'description' => $transactionDescription,
            'notes' => $data['notes'] ?? null,
            'meta' => [
                'original_document_currency' => $invoiceCurrency,
                'amount_in_document_currency_cents' => $amountInInvoiceCurrencyCents,
            ],
        ]);
    }

    public function approveDraft(?Carbon $approvedAt = null): void
    {
        if (! $this->isDraft()) {
            throw new RuntimeException('Invoice is not in draft status.');
        }

        $this->createApprovalTransaction();

        $approvedAt ??= now();

        $this->update([
            'approved_at' => $approvedAt,
            'status' => InvoiceStatus::Unsent,
        ]);
    }

    public function createApprovalTransaction(): void
    {
        $baseDescription = "{$this->client->name}: Invoice #{$this->invoice_number}";

        $journalEntryData = [];

        $totalInInvoiceCurrency = $this->total;
        $journalEntryData[] = [
            'type' => JournalEntryType::Debit,
            'account_id' => Account::getAccountsReceivableAccount($this->company_id)->id,
            'amount_in_invoice_currency' => $totalInInvoiceCurrency,
            'description' => $baseDescription,
        ];

        $totalLineItemSubtotalInInvoiceCurrency = (int) $this->lineItems()->sum('subtotal');
        $invoiceDiscountTotalInInvoiceCurrency = (int) $this->discount_total;
        $remainingDiscountInInvoiceCurrency = $invoiceDiscountTotalInInvoiceCurrency;

        foreach ($this->lineItems as $index => $lineItem) {
            $lineItemDescription = "{$baseDescription} › {$lineItem->offering->name}";
            $lineItemSubtotalInInvoiceCurrency = $lineItem->subtotal;

            $journalEntryData[] = [
                'type' => JournalEntryType::Credit,
                'account_id' => $lineItem->offering->income_account_id,
                'amount_in_invoice_currency' => $lineItemSubtotalInInvoiceCurrency,
                'description' => $lineItemDescription,
            ];

            foreach ($lineItem->adjustments as $adjustment) {
                $adjustmentAmountInInvoiceCurrency = $lineItem->calculateAdjustmentTotalAmount($adjustment);

                $journalEntryData[] = [
                    'type' => $adjustment->category->isDiscount() ? JournalEntryType::Debit : JournalEntryType::Credit,
                    'account_id' => $adjustment->account_id,
                    'amount_in_invoice_currency' => $adjustmentAmountInInvoiceCurrency,
                    'description' => $lineItemDescription,
                ];
            }

            // Handle per-document discount allocation
            if ($this->discount_method->isPerDocument() && $totalLineItemSubtotalInInvoiceCurrency > 0) {
                $lineItemSubtotalInInvoiceCurrency = $lineItem->subtotal;

                if ($index === $this->lineItems->count() - 1) {
                    $lineItemDiscountInInvoiceCurrency = $remainingDiscountInInvoiceCurrency;
                } else {
                    $lineItemDiscountInInvoiceCurrency = (int) round(
                        ($lineItemSubtotalInInvoiceCurrency / $totalLineItemSubtotalInInvoiceCurrency) * $invoiceDiscountTotalInInvoiceCurrency
                    );
                    $remainingDiscountInInvoiceCurrency -= $lineItemDiscountInInvoiceCurrency;
                }

                if ($lineItemDiscountInInvoiceCurrency > 0) {
                    $journalEntryData[] = [
                        'type' => JournalEntryType::Debit,
                        'account_id' => Account::getSalesDiscountAccount($this->company_id)->id,
                        'amount_in_invoice_currency' => $lineItemDiscountInInvoiceCurrency,
                        'description' => "{$lineItemDescription} (Proportional Discount)",
                    ];
                }
            }
        }

        // Convert amounts to default currency
        $totalDebitsInDefaultCurrency = 0;
        $totalCreditsInDefaultCurrency = 0;

        foreach ($journalEntryData as &$entry) {
            $entry['amount_in_default_currency'] = $this->formatAmountToDefaultCurrency($entry['amount_in_invoice_currency']);

            if ($entry['type'] === JournalEntryType::Debit) {
                $totalDebitsInDefaultCurrency += $entry['amount_in_default_currency'];
            } else {
                $totalCreditsInDefaultCurrency += $entry['amount_in_default_currency'];
            }
        }

        unset($entry);

        // Handle currency conversion imbalance
        $imbalance = $totalDebitsInDefaultCurrency - $totalCreditsInDefaultCurrency;
        if ($imbalance !== 0) {
            $targetType = $imbalance > 0 ? JournalEntryType::Credit : JournalEntryType::Debit;
            $adjustmentAmount = abs($imbalance);

            // Find last entry of target type and adjust it
            $lastKey = array_key_last(array_filter($journalEntryData, fn ($entry) => $entry['type'] === $targetType, ARRAY_FILTER_USE_BOTH));
            $journalEntryData[$lastKey]['amount_in_default_currency'] += $adjustmentAmount;

            if ($targetType === JournalEntryType::Debit) {
                $totalDebitsInDefaultCurrency += $adjustmentAmount;
            } else {
                $totalCreditsInDefaultCurrency += $adjustmentAmount;
            }
        }

        if ($totalDebitsInDefaultCurrency !== $totalCreditsInDefaultCurrency) {
            throw new Exception('Journal entries do not balance for Invoice #' . $this->invoice_number . '. Debits: ' . $totalDebitsInDefaultCurrency . ', Credits: ' . $totalCreditsInDefaultCurrency);
        }

        // Create the transaction using the sum of debits
        $transaction = $this->transactions()->create([
            'company_id' => $this->company_id,
            'type' => TransactionType::Journal,
            'posted_at' => $this->date,
            'amount' => $totalDebitsInDefaultCurrency,
            'description' => 'Invoice Approval for Invoice #' . $this->invoice_number,
        ]);

        // Create all journal entries
        foreach ($journalEntryData as $entry) {
            $transaction->journalEntries()->create([
                'company_id' => $this->company_id,
                'type' => $entry['type'],
                'account_id' => $entry['account_id'],
                'amount' => $entry['amount_in_default_currency'],
                'description' => $entry['description'],
            ]);
        }
    }

    public function updateApprovalTransaction(): void
    {
        $this->approvalTransaction?->delete();

        $this->createApprovalTransaction();
    }

    public function convertAmountToDefaultCurrency(int $amountCents): int
    {
        $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
        $needsConversion = $this->currency_code !== $defaultCurrency;

        if ($needsConversion) {
            return CurrencyConverter::convertBalance($amountCents, $this->currency_code, $defaultCurrency);
        }

        return $amountCents;
    }

    public function formatAmountToDefaultCurrency(int $amountCents): int
    {
        return $this->convertAmountToDefaultCurrency($amountCents);
    }

    // TODO: Potentially handle this another way
    public static function getBlockedApproveAction(string $action = Action::class): Action
    {
        return $action::make('blockedApprove')
            ->label('Approve')
            ->icon('heroicon-m-check-circle')
            ->visible(fn (self $record) => $record->canBeApproved() && $record->hasInactiveAdjustments())
            ->requiresConfirmation()
            ->modalAlignment(Alignment::Start)
            ->modalIconColor('danger')
            ->modalDescription(function (self $record) {
                $inactiveAdjustments = collect();

                foreach ($record->lineItems as $lineItem) {
                    foreach ($lineItem->adjustments as $adjustment) {
                        if ($adjustment->isInactive() && $inactiveAdjustments->doesntContain($adjustment->name)) {
                            $inactiveAdjustments->push($adjustment->name);
                        }
                    }
                }

                $output = "<p class='text-sm mb-4'>This invoice contains inactive adjustments that need to be addressed before approval:</p>";
                $output .= "<ul role='list' class='list-disc list-inside space-y-1 text-sm'>";

                foreach ($inactiveAdjustments as $name) {
                    $output .= "<li class='py-1'><span class='font-medium'>{$name}</span></li>";
                }

                $output .= '</ul>';
                $output .= "<p class='text-sm mt-4'>Please update these adjustments before approving the invoice.</p>";

                return new HtmlString($output);
            })
            ->modalSubmitAction(function (Action $action, self $record) {
                $action->label('Edit Invoice')
                    ->url(EditInvoice::getUrl(['record' => $record->id]));
            });
    }

    public static function getApproveDraftAction(string $action = Action::class): Action
    {
        return $action::make('approveDraft')
            ->label('Approve')
            ->icon('heroicon-m-check-circle')
            ->visible(function (self $record) {
                return $record->canBeApproved();
            })
            ->requiresConfirmation()
            ->databaseTransaction()
            ->successNotificationTitle('Invoice approved')
            ->action(function (self $record, Action $action, Component $livewire) {
                if ($record->hasInactiveAdjustments()) {
                    $isViewPage = $livewire instanceof ViewInvoice;

                    if (! $isViewPage) {
                        redirect(ViewInvoice::getUrl(['record' => $record->id]));
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Cannot approve invoice')
                            ->body('This invoice has inactive adjustments that must be addressed first.')
                            ->persistent()
                            ->send();
                    }
                } else {
                    $record->approveDraft();

                    $action->success();
                }
            });
    }

    public static function getMarkAsSentAction(string $action = Action::class): Action
    {
        return $action::make('markAsSent')
            ->label('Mark as sent')
            ->icon('heroicon-m-paper-airplane')
            ->visible(static function (self $record) {
                return $record->canBeMarkedAsSent();
            })
            ->successNotificationTitle('Invoice sent')
            ->action(function (self $record, Action $action) {
                $record->markAsSent();

                $action->success();
            });
    }

    public function markAsSent(?Carbon $sentAt = null): void
    {
        $sentAt ??= now();

        $this->update([
            'status' => InvoiceStatus::Sent,
            'last_sent_at' => $sentAt,
        ]);
    }

    public function markAsViewed(?Carbon $viewedAt = null): void
    {
        $viewedAt ??= now();

        $this->update([
            'status' => InvoiceStatus::Viewed,
            'last_viewed_at' => $viewedAt,
        ]);
    }

    public static function getReplicateAction(string $action = ReplicateAction::class): Action
    {
        return $action::make()
            ->excludeAttributes([
                'status',
                'amount_paid',
                'amount_due',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
                'invoice_number',
                'date',
                'due_date',
                'approved_at',
                'paid_at',
                'last_sent_at',
                'last_viewed_at',
            ])
            ->modal(false)
            ->beforeReplicaSaved(function (self $original, self $replica) {
                $replica->status = InvoiceStatus::Draft;
                $replica->invoice_number = self::getNextDocumentNumber();
                $replica->date = now();
                $replica->due_date = now()->addDays($original->company->defaultInvoice->payment_terms->getDays());
            })
            ->databaseTransaction()
            ->after(function (self $original, self $replica) {
                $original->replicateLineItems($replica);
            })
            ->successRedirectUrl(static function (self $replica) {
                return InvoiceResource::getUrl('edit', ['record' => $replica]);
            });
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
