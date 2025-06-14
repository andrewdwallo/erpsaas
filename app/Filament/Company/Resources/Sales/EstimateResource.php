<?php

namespace App\Filament\Company\Resources\Sales;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentStatus;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\EstimateStatus;
use App\Enums\Setting\PaymentTerms;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\EstimatesRelationManager;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\CreateEstimate;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\EditEstimate;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\ListEstimates;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\ViewEstimate;
use App\Filament\Company\Resources\Sales\EstimateResource\Widgets\EstimateOverview;
use App\Filament\Forms\Components\CreateAdjustmentSelect;
use App\Filament\Forms\Components\CreateClientSelect;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CreateOfferingSelect;
use App\Filament\Forms\Components\CustomTableRepeater;
use App\Filament\Forms\Components\DocumentFooterSection;
use App\Filament\Forms\Components\DocumentHeaderSection;
use App\Filament\Forms\Components\DocumentTotals;
use App\Filament\Tables\Actions\ReplicateBulkAction;
use App\Filament\Tables\Columns;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\Accounting\Adjustment;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Estimate;
use App\Models\Common\Client;
use App\Models\Common\Offering;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\Utilities\RateCalculator;
use Awcodes\TableRepeater\Header;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EstimateResource extends Resource
{
    protected static ?string $model = Estimate::class;

    public static function form(Schema $schema): Schema
    {
        $company = Auth::user()->currentCompany;

        $settings = $company->defaultEstimate;

        return $schema
            ->components([
                DocumentHeaderSection::make('Estimate Header')
                    ->defaultHeader($settings->header)
                    ->defaultSubheader($settings->subheader),
                Section::make('Estimate Details')
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
                                CreateCurrencySelect::make('currency_code'),
                            ]),
                            Group::make([
                                TextInput::make('estimate_number')
                                    ->label('Estimate number')
                                    ->default(static fn () => Estimate::getNextDocumentNumber()),
                                TextInput::make('reference_number')
                                    ->label('Reference number'),
                                Cluster::make([
                                    DatePicker::make('date')
                                        ->label('Estimate date')
                                        ->live()
                                        ->default(now())
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                            $date = $state;
                                            $expirationDate = $get('expiration_date');

                                            if ($date && $expirationDate && $date > $expirationDate) {
                                                $set('expiration_date', $date);
                                            }

                                            $paymentTerms = $get('payment_terms');
                                            if ($date && $paymentTerms && $paymentTerms !== 'custom') {
                                                $terms = PaymentTerms::parse($paymentTerms);
                                                $set('expiration_date', Carbon::parse($date)->addDays($terms->getDays())->toDateString());
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
                                                $set('expiration_date', Carbon::parse($date)->addDays($terms->getDays())->toDateString());
                                            }
                                        }),
                                ])
                                    ->label('Estimate date')
                                    ->columns(3),
                                DatePicker::make('expiration_date')
                                    ->label('Expiration date')
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

                                        $date = $get('date');
                                        $paymentTerms = $get('payment_terms');

                                        if (! $date || $paymentTerms === 'custom') {
                                            return;
                                        }

                                        $term = PaymentTerms::parse($paymentTerms);
                                        $expected = Carbon::parse($date)->addDays($term->getDays());

                                        if (! Carbon::parse($state)->isSameDay($expected)) {
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
                        CustomTableRepeater::make('lineItems')
                            ->hiddenLabel()
                            ->relationship()
                            ->saveRelationshipsUsing(null)
                            ->dehydrated(true)
                            ->reorderable()
                            ->orderColumn('line_number')
                            ->reorderAtStart()
                            ->cloneable()
                            ->addActionLabel('Add an item')
                            ->headers(function (Get $get) use ($settings) {
                                $hasDiscounts = DocumentDiscountMethod::parse($get('discount_method'))->isPerLineItem();

                                $headers = [
                                    Header::make($settings->resolveColumnLabel('item_name', 'Items'))
                                        ->width('30%'),
                                    Header::make($settings->resolveColumnLabel('unit_name', 'Quantity'))
                                        ->width('10%'),
                                    Header::make($settings->resolveColumnLabel('price_name', 'Price'))
                                        ->width('10%'),
                                ];

                                if ($hasDiscounts) {
                                    $headers[] = Header::make('Adjustments')->width('30%');
                                } else {
                                    $headers[] = Header::make('Taxes')->width('30%');
                                }

                                $headers[] = Header::make($settings->resolveColumnLabel('amount_name', 'Amount'))
                                    ->width('10%')
                                    ->align('right');

                                return $headers;
                            })
                            ->schema([
                                Group::make([
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
                                ])->columnSpan(1),
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
                                Group::make([
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
                                ])->columnSpan(1),
                                Placeholder::make('total')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'text-left sm:text-right'])
                                    ->content(function (Get $get) {
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
                            ->type(DocumentType::Estimate),
                        Textarea::make('terms')
                            ->default($settings->terms)
                            ->columnSpanFull(),
                    ]),
                DocumentFooterSection::make('Estimate Footer')
                    ->defaultFooter($settings->footer),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expiration_date')
            ->columns([
                Columns::id(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('expiration_date')
                    ->label('Expiration date')
                    ->asRelativeDay()
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('estimate_number')
                    ->label('Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->sortable()
                    ->searchable()
                    ->hiddenOn(EstimatesRelationManager::class),
                TextColumn::make('total')
                    ->currencyWithConversion(static fn (Estimate $record) => $record->currency_code)
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn(EstimatesRelationManager::class),
                SelectFilter::make('status')
                    ->options(EstimateStatus::class)
                    ->native(false),
                DateRangeFilter::make('date')
                    ->fromLabel('From date')
                    ->untilLabel('To date')
                    ->indicatorLabel('Date'),
                DateRangeFilter::make('expiration_date')
                    ->fromLabel('From expiration date')
                    ->untilLabel('To expiration date')
                    ->indicatorLabel('Due'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make()
                            ->url(static fn (Estimate $record) => EditEstimate::getUrl(['record' => $record])),
                        ViewAction::make()
                            ->url(static fn (Estimate $record) => ViewEstimate::getUrl(['record' => $record])),
                        Estimate::getReplicateAction(ReplicateAction::class),
                        Estimate::getApproveDraftAction(Action::class),
                        Estimate::getMarkAsSentAction(Action::class),
                        Estimate::getMarkAsAcceptedAction(Action::class),
                        Estimate::getMarkAsDeclinedAction(Action::class),
                        Estimate::getConvertToInvoiceAction(Action::class),
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
                        ->modalDescription('Replicating estimates will also replicate their line items. Are you sure you want to proceed?')
                        ->successNotificationTitle('Estimates replicated successfully')
                        ->failureNotificationTitle('Failed to replicate estimates')
                        ->databaseTransaction()
                        ->deselectRecordsAfterCompletion()
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
                        ->beforeReplicaSaved(function (Estimate $replica) {
                            $replica->status = EstimateStatus::Draft;
                            $replica->estimate_number = Estimate::getNextDocumentNumber();
                            $replica->date = now();
                            $replica->expiration_date = now()->addDays($replica->company->defaultInvoice->payment_terms->getDays());
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
                        ->successNotificationTitle('Estimates approved')
                        ->failureNotificationTitle('Failed to approve estimates')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Estimate $record) => ! $record->canBeApproved());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Approval failed')
                                    ->body('Only draft estimates can be approved. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Estimate $record) {
                                $record->approveDraft();
                            });

                            $action->success();
                        }),
                    BulkAction::make('markAsSent')
                        ->label('Mark as sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->databaseTransaction()
                        ->successNotificationTitle('Estimates sent')
                        ->failureNotificationTitle('Failed to mark estimates as sent')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Estimate $record) => ! $record->canBeMarkedAsSent());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Sending failed')
                                    ->body('Only unsent estimates can be marked as sent. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Estimate $record) {
                                $record->markAsSent();
                            });

                            $action->success();
                        }),
                    BulkAction::make('markAsAccepted')
                        ->label('Mark as accepted')
                        ->icon('heroicon-o-check-badge')
                        ->databaseTransaction()
                        ->successNotificationTitle('Estimates accepted')
                        ->failureNotificationTitle('Failed to mark estimates as accepted')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Estimate $record) => ! $record->canBeMarkedAsAccepted());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Acceptance failed')
                                    ->body('Only sent estimates that haven\'t been accepted can be marked as accepted. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Estimate $record) {
                                $record->markAsAccepted();
                            });

                            $action->success();
                        }),
                    BulkAction::make('markAsDeclined')
                        ->label('Mark as declined')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->databaseTransaction()
                        ->color('danger')
                        ->modalHeading('Mark Estimates as Declined')
                        ->modalDescription('Are you sure you want to mark the selected estimates as declined? This action cannot be undone.')
                        ->successNotificationTitle('Estimates declined')
                        ->failureNotificationTitle('Failed to mark estimates as declined')
                        ->before(function (Collection $records, BulkAction $action) {
                            $isInvalid = $records->contains(fn (Estimate $record) => ! $record->canBeMarkedAsDeclined());

                            if ($isInvalid) {
                                Notification::make()
                                    ->title('Declination failed')
                                    ->body('Only sent estimates that haven\'t been declined can be marked as declined. Please adjust your selection and try again.')
                                    ->persistent()
                                    ->danger()
                                    ->send();

                                $action->cancel(true);
                            }
                        })
                        ->action(function (Collection $records, BulkAction $action) {
                            $records->each(function (Estimate $record) {
                                $record->markAsDeclined();
                            });

                            $action->success();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstimates::route('/'),
            'create' => CreateEstimate::route('/create'),
            'view' => ViewEstimate::route('/{record}'),
            'edit' => EditEstimate::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            EstimateOverview::class,
        ];
    }
}
