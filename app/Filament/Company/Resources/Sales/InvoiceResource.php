<?php

namespace App\Filament\Company\Resources\Sales;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentStatus;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Setting\PaymentTerms;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\EditInvoice;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ListInvoices;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\RecordPayments;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Company\Resources\Sales\InvoiceResource\Widgets\InvoiceOverview;
use App\Filament\Forms\Components\CreateAdjustmentSelect;
use App\Filament\Forms\Components\CreateClientSelect;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CreateOfferingSelect;
use App\Filament\Forms\Components\DocumentFooterSection;
use App\Filament\Forms\Components\DocumentHeaderSection;
use App\Filament\Forms\Components\DocumentTotals;
use App\Filament\Tables\Actions\ReplicateBulkAction;
use App\Filament\Tables\Columns;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\Accounting\Adjustment;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Invoice;
use App\Models\Common\Client;
use App\Models\Common\Offering;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\Utilities\RateCalculator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    public static function form(Schema $schema): Schema
    {
        $company = Auth::user()->currentCompany;

        $settings = $company->defaultInvoice;

        return $schema
            ->components([
                DocumentHeaderSection::make('Invoice Header')
                    ->columnSpanFull()
                    ->defaultHeader($settings->header)
                    ->defaultSubheader($settings->subheader),
                Section::make('Invoice Details')
                    ->schema([
                        Flex::make([
                            Group::make([
                                CreateClientSelect::make('client_id')
                                    ->label('Client')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $currencyCode = Client::find($state)?->currency_code;

                                        if ($currencyCode) {
                                            $set('currency_code', $currencyCode);
                                        }
                                    }),
                                CreateCurrencySelect::make('currency_code')
                                    ->disabled(function (?Invoice $record) {
                                        return $record?->hasPayments();
                                    }),
                            ]),
                            Group::make([
                                TextInput::make('invoice_number')
                                    ->label('Invoice number')
                                    ->default(static fn () => Invoice::getNextDocumentNumber()),
                                TextInput::make('order_number')
                                    ->label('P.O/S.O Number'),
                                FusedGroup::make([
                                    DatePicker::make('date')
                                        ->label('Invoice date')
                                        ->live()
                                        ->default(now())
                                        ->disabled(function (?Invoice $record) {
                                            return $record?->hasPayments();
                                        })
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            $date = $state;
                                            $dueDate = $get('due_date');

                                            if ($date && $dueDate && $date > $dueDate) {
                                                $set('due_date', $date);
                                            }

                                            // Update due date based on payment terms if selected
                                            $paymentTerms = $get('payment_terms');
                                            if ($date && $paymentTerms && $paymentTerms !== 'custom') {
                                                $terms = PaymentTerms::parse($paymentTerms);
                                                $set('due_date', Carbon::parse($date)->addDays($terms->getDays())->toDateString());
                                            }
                                        }),
                                    Select::make('payment_terms')
                                        ->label('Payment terms')
                                        ->options(function () {
                                            return collect(PaymentTerms::cases())
                                                ->mapWithKeys(function (PaymentTerms $paymentTerm) {
                                                    return [$paymentTerm->value => $paymentTerm->getLabel()];
                                                })
                                                ->put('custom', 'Custom')
                                                ->toArray();
                                        })
                                        ->selectablePlaceholder(false)
                                        ->default($settings->payment_terms->value)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            if (! $state || $state === 'custom') {
                                                return;
                                            }

                                            $date = $get('date');
                                            if ($date) {
                                                $terms = PaymentTerms::parse($state);
                                                $set('due_date', Carbon::parse($date)->addDays($terms->getDays())->toDateString());
                                            }
                                        }),
                                ])
                                    ->label('Invoice date')
                                    ->columns(3),
                                DatePicker::make('due_date')
                                    ->label('Payment due')
                                    ->default(function () use ($settings) {
                                        return now()->addDays($settings->payment_terms->getDays());
                                    })
                                    ->minDate(static function (Get $get) {
                                        return $get('date') ?? now();
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if (! $state) {
                                            return;
                                        }

                                        $invoiceDate = $get('date');
                                        $paymentTerms = $get('payment_terms');

                                        if (! $invoiceDate || $paymentTerms === 'custom' || ! $paymentTerms) {
                                            return;
                                        }

                                        $term = PaymentTerms::parse($paymentTerms);
                                        $expectedDueDate = Carbon::parse($invoiceDate)->addDays($term->getDays());

                                        if (! Carbon::parse($state)->isSameDay($expectedDueDate)) {
                                            $set('payment_terms', 'custom');
                                        }
                                    }),
                                Select::make('discount_method')
                                    ->label('Discount method')
                                    ->options(DocumentDiscountMethod::class)
                                    ->softRequired()
                                    ->default($settings->discount_method)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $discountMethod = DocumentDiscountMethod::parse($state);

                                        if ($discountMethod->isPerDocument()) {
                                            $set('lineItems.*.salesDiscounts', []);
                                        }
                                    })
                                    ->live(),
                            ])->grow(true),
                        ])->from('md'),
                        Repeater::make('lineItems')
                            ->hiddenLabel()
                            ->relationship()
                            ->saveRelationshipsUsing(null)
                            ->dehydrated(true)
                            ->reorderable()
                            ->orderColumn('line_number')
                            ->cloneable()
                            ->addActionLabel('Add an item')
                            ->table(function (Get $get) use ($settings) {
                                $hasDiscounts = DocumentDiscountMethod::parse($get('discount_method'))->isPerLineItem();

                                $headers = [
                                    TableColumn::make($settings->resolveColumnLabel('item_name', 'Items'))
                                        ->width('25%'),
                                    TableColumn::make('Description')
                                        ->width('20%'),
                                    TableColumn::make($settings->resolveColumnLabel('unit_name', 'Quantity'))
                                        ->width('10%'),
                                    TableColumn::make($settings->resolveColumnLabel('price_name', 'Price'))
                                        ->width('10%'),
                                    TableColumn::make('Taxes')
                                        ->width('15%'),
                                ];

                                if ($hasDiscounts) {
                                    $headers[] = TableColumn::make('Discounts')->width('10%');
                                }

                                $headers[] = TableColumn::make($settings->resolveColumnLabel('amount_name', 'Amount'))
                                    ->width('10%')
                                    ->alignEnd();

                                return $headers;
                            })
                            ->schema([
                                CreateOfferingSelect::make('offering_id')
                                    ->label('Item')
                                    ->hiddenLabel()
                                    ->placeholder('Select item')
                                    ->required()
                                    ->live()
                                    ->inlineSuffix()
                                    ->sellable()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state, ?DocumentLineItem $record) {
                                        $offeringId = $state;
                                        $discountMethod = DocumentDiscountMethod::parse($get('../../discount_method'));
                                        $isPerLineItem = $discountMethod->isPerLineItem();

                                        $existingTaxIds = [];
                                        $existingDiscountIds = [];

                                        if ($record) {
                                            $existingTaxIds = $record->salesTaxes()->pluck('adjustments.id')->toArray();
                                            if ($isPerLineItem) {
                                                $existingDiscountIds = $record->salesDiscounts()->pluck('adjustments.id')->toArray();
                                            }
                                        }

                                        $with = [
                                            'salesTaxes' => static function ($query) use ($existingTaxIds) {
                                                $query->where(static function ($query) use ($existingTaxIds) {
                                                    $query->where('status', AdjustmentStatus::Active)
                                                        ->orWhereIn('adjustments.id', $existingTaxIds);
                                                });
                                            },
                                        ];

                                        if ($isPerLineItem) {
                                            $with['salesDiscounts'] = static function ($query) use ($existingDiscountIds) {
                                                $query->where(static function ($query) use ($existingDiscountIds) {
                                                    $query->where('status', AdjustmentStatus::Active)
                                                        ->orWhereIn('adjustments.id', $existingDiscountIds);
                                                });
                                            };
                                        }

                                        $offeringRecord = Offering::with($with)->find($offeringId);

                                        if (! $offeringRecord) {
                                            return;
                                        }

                                        $unitPrice = CurrencyConverter::convertCentsToFormatSimple($offeringRecord->price, 'USD');

                                        $set('description', $offeringRecord->description);
                                        $set('unit_price', $unitPrice);
                                        $set('salesTaxes', $offeringRecord->salesTaxes->pluck('id')->toArray());

                                        if ($isPerLineItem) {
                                            $set('salesDiscounts', $offeringRecord->salesDiscounts->pluck('id')->toArray());
                                        }
                                    }),
                                TextInput::make('description')
                                    ->placeholder('Enter item description')
                                    ->hiddenLabel(),
                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->maxValue(9999999999.99)
                                    ->default(1),
                                TextInput::make('unit_price')
                                    ->hiddenLabel()
                                    ->money(useAffix: false)
                                    ->live()
                                    ->default(0),
                                CreateAdjustmentSelect::make('salesTaxes')
                                    ->label('Taxes')
                                    ->hiddenLabel()
                                    ->placeholder('Select taxes')
                                    ->category(AdjustmentCategory::Tax)
                                    ->type(AdjustmentType::Sales)
                                    ->adjustmentsRelationship('salesTaxes')
                                    ->saveRelationshipsUsing(null)
                                    ->dehydrated(true)
                                    ->inlineSuffix()
                                    ->preload()
                                    ->multiple()
                                    ->live()
                                    ->searchable(),
                                CreateAdjustmentSelect::make('salesDiscounts')
                                    ->label('Discounts')
                                    ->hiddenLabel()
                                    ->placeholder('Select discounts')
                                    ->category(AdjustmentCategory::Discount)
                                    ->type(AdjustmentType::Sales)
                                    ->adjustmentsRelationship('salesDiscounts')
                                    ->saveRelationshipsUsing(null)
                                    ->dehydrated(true)
                                    ->inlineSuffix()
                                    ->multiple()
                                    ->live()
                                    ->hidden(function (Get $get) {
                                        $discountMethod = DocumentDiscountMethod::parse($get('../../discount_method'));

                                        return $discountMethod->isPerDocument();
                                    })
                                    ->searchable(),
                                TextEntry::make('total')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'text-left sm:text-right'])
                                    ->state(function (Get $get) {
                                        $quantity = max((float) ($get('quantity') ?? 0), 0);
                                        $unitPrice = CurrencyConverter::isValidAmount($get('unit_price'), 'USD')
                                            ? CurrencyConverter::convertToFloat($get('unit_price'), 'USD')
                                            : 0;
                                        $salesTaxes = $get('salesTaxes') ?? [];
                                        $salesDiscounts = $get('salesDiscounts') ?? [];
                                        $currencyCode = $get('../../currency_code') ?? CurrencyAccessor::getDefaultCurrency();

                                        $subtotal = $quantity * $unitPrice;

                                        $subtotalInCents = CurrencyConverter::convertToCents($subtotal, $currencyCode);

                                        $taxAmountInCents = Adjustment::whereIn('id', $salesTaxes)
                                            ->get()
                                            ->sum(function (Adjustment $adjustment) use ($subtotalInCents) {
                                                if ($adjustment->computation->isPercentage()) {
                                                    return RateCalculator::calculatePercentage($subtotalInCents, $adjustment->getRawOriginal('rate'));
                                                } else {
                                                    return $adjustment->getRawOriginal('rate');
                                                }
                                            });

                                        $discountAmountInCents = Adjustment::whereIn('id', $salesDiscounts)
                                            ->get()
                                            ->sum(function (Adjustment $adjustment) use ($subtotalInCents) {
                                                if ($adjustment->computation->isPercentage()) {
                                                    return RateCalculator::calculatePercentage($subtotalInCents, $adjustment->getRawOriginal('rate'));
                                                } else {
                                                    return $adjustment->getRawOriginal('rate');
                                                }
                                            });

                                        // Final total
                                        $totalInCents = $subtotalInCents + ($taxAmountInCents - $discountAmountInCents);

                                        return CurrencyConverter::formatCentsToMoney($totalInCents, $currencyCode);
                                    }),
                            ]),
                        DocumentTotals::make()
                            ->type(DocumentType::Invoice),
                        Textarea::make('terms')
                            ->default($settings->terms)
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
                DocumentFooterSection::make('Invoice Footer')
                    ->columnSpanFull()
                    ->defaultFooter($settings->footer),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire) {
                if (property_exists($livewire, 'recurringInvoice')) {
                    $recurringInvoiceId = $livewire->recurringInvoice;

                    if (! empty($recurringInvoiceId)) {
                        $query->where('recurring_invoice_id', $recurringInvoiceId);
                    }
                }

                return $query;
            })
            ->columns([
                Columns::id(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->asRelativeDay()
                    ->sortable()
                    ->hideOnTabs(['draft']),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('Number')
                    ->searchable()
                    ->description(function (Invoice $record) {
                        return $record->source_type?->getLabel();
                    })
                    ->sortable(),
                TextColumn::make('client.name')
                    ->sortable()
                    ->searchable()
                    ->hiddenOn(InvoicesRelationManager::class),
                TextColumn::make('total')
                    ->currencyWithConversion(static fn (Invoice $record) => $record->currency_code)
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),
                TextColumn::make('amount_paid')
                    ->label('Amount paid')
                    ->currencyWithConversion(static fn (Invoice $record) => $record->currency_code)
                    ->sortable()
                    ->alignEnd()
                    ->showOnTabs(['unpaid']),
                TextColumn::make('amount_due')
                    ->label('Amount due')
                    ->currencyWithConversion(static fn (Invoice $record) => $record->currency_code)
                    ->sortable()
                    ->alignEnd()
                    ->hideOnTabs(['draft']),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn(InvoicesRelationManager::class),
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class)
                    ->multiple(),
                TernaryFilter::make('has_payments')
                    ->label('Has payments')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('payments'),
                        false: fn (Builder $query) => $query->whereDoesntHave('payments'),
                    ),
                SelectFilter::make('source_type')
                    ->label('Source type')
                    ->options([
                        DocumentType::Estimate->value => DocumentType::Estimate->getLabel(),
                        DocumentType::RecurringInvoice->value => DocumentType::RecurringInvoice->getLabel(),
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        $sourceType = $data['value'] ?? null;

                        return match ($sourceType) {
                            DocumentType::Estimate->value => $query->whereNotNull('estimate_id'),
                            DocumentType::RecurringInvoice->value => $query->whereNotNull('recurring_invoice_id'),
                            default => $query,
                        };
                    }),
                DateRangeFilter::make('date')
                    ->fromLabel('From date')
                    ->untilLabel('To date')
                    ->indicatorLabel('Date'),
                DateRangeFilter::make('due_date')
                    ->fromLabel('From due date')
                    ->untilLabel('To due date')
                    ->indicatorLabel('Due'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make()
                            ->url(static fn (Invoice $record) => EditInvoice::getUrl(['record' => $record])),
                        ViewAction::make()
                            ->url(static fn (Invoice $record) => ViewInvoice::getUrl(['record' => $record])),
                        Invoice::getReplicateAction(ReplicateAction::class),
                        Invoice::getApproveDraftAction(Action::class),
                        Invoice::getMarkAsSentAction(Action::class),
                        Action::make('recordPayment')
                            ->label('Record Payment')
                            ->icon('heroicon-m-credit-card')
                            ->visible(function (Invoice $record) {
                                return $record->canRecordPayment();
                            })
                            ->url(fn (Invoice $record) => RecordPayments::getUrl([
                                'tableFilters' => [
                                    'client_id' => ['value' => $record->client_id],
                                    'currency_code' => ['value' => $record->currency_code],
                                ],
                                'invoiceId' => $record->id,
                            ]))
                            ->openUrlInNewTab(false),
                    ])->dropdown(false),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ReplicateBulkAction::make()
                        ->label('Replicate')
                        ->modalWidth(Width::Large)
                        ->modalDescription('Replicating invoices will also replicate their line items. Are you sure you want to proceed?')
                        ->successNotificationTitle('Invoices replicated successfully')
                        ->failureNotificationTitle('Failed to replicate invoices')
                        ->databaseTransaction()
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
                        ->beforeReplicaSaved(function (Invoice $replica) {
                            $replica->status = InvoiceStatus::Draft;
                            $replica->invoice_number = Invoice::getNextDocumentNumber();
                            $replica->date = now();
                            $replica->due_date = now()->addDays($replica->company->defaultInvoice->payment_terms->getDays());
                        })
                        ->withReplicatedRelationships(['lineItems'])
                        ->withExcludedRelationshipAttributes('lineItems', [
                            'subtotal',
                            'total',
                            'created_by',
                            'updated_by',
                            'created_at',
                            'updated_at',
                        ]),
                    BulkAction::make('approveDrafts')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->databaseTransaction()
                        ->successNotificationTitle('Invoices approved')
                        ->failureNotificationTitle('Failed to Approve Invoices')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Invoice $record) => ! $record->canBeApproved());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Approval failed')
                                    ->body('Only draft invoices can be approved. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Invoice $record) {
                                $record->approveDraft();
                            });

                            $action->success();
                        }),
                    BulkAction::make('markAsSent')
                        ->label('Mark as sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->databaseTransaction()
                        ->successNotificationTitle('Invoices sent')
                        ->failureNotificationTitle('Failed to Mark Invoices as Sent')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Invoice $record) => ! $record->canBeMarkedAsSent());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Sending failed')
                                    ->body('Only unsent invoices can be marked as sent. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Invoice $record) {
                                $record->markAsSent();
                            });

                            $action->success();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'record-payments' => RecordPayments::route('/record-payments'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            InvoiceOverview::class,
        ];
    }
}
