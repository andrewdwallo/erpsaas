<?php

namespace App\Filament\Company\Resources\Sales;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentStatus;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\RecurringInvoiceStatus;
use App\Enums\Setting\PaymentTerms;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\RecurringInvoicesRelationManager;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\CreateRecurringInvoice;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\EditRecurringInvoice;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\ListRecurringInvoices;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\ViewRecurringInvoice;
use App\Filament\Forms\Components\CreateAdjustmentSelect;
use App\Filament\Forms\Components\CreateClientSelect;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CreateOfferingSelect;
use App\Filament\Forms\Components\CustomTableRepeater;
use App\Filament\Forms\Components\DocumentFooterSection;
use App\Filament\Forms\Components\DocumentHeaderSection;
use App\Filament\Forms\Components\DocumentTotals;
use App\Filament\Tables\Columns;
use App\Models\Accounting\Adjustment;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Common\Client;
use App\Models\Common\Offering;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use App\Utilities\RateCalculator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RecurringInvoiceResource extends Resource
{
    protected static ?string $model = RecurringInvoice::class;

    public static function form(Schema $schema): Schema
    {
        $company = Auth::user()->currentCompany;

        $settings = $company->defaultInvoice;

        return $schema
            ->components([
                DocumentHeaderSection::make('Invoice Header')
                    ->defaultHeader($settings->header)
                    ->defaultSubheader($settings->subheader)
                    ->columnSpanFull(),
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
                                CreateCurrencySelect::make('currency_code'),
                            ]),
                            Group::make([
                                Placeholder::make('invoice_number')
                                    ->label('Invoice number')
                                    ->content('Auto-generated'),
                                TextInput::make('order_number')
                                    ->label('P.O/S.O Number'),
                                Placeholder::make('date')
                                    ->label('Invoice date')
                                    ->content('Auto-generated'),
                                Select::make('payment_terms')
                                    ->label('Payment due')
                                    ->options(PaymentTerms::class)
                                    ->softRequired()
                                    ->default($settings->payment_terms)
                                    ->live(),
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
                            ->columnSpanFull()
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
            ->defaultSort('next_date')
            ->columns([
                Columns::id(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('client.name')
                    ->sortable()
                    ->searchable()
                    ->hiddenOn(RecurringInvoicesRelationManager::class),
                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->getStateUsing(function (RecurringInvoice $record) {
                        return $record->getScheduleDescription();
                    })
                    ->description(function (RecurringInvoice $record) {
                        return $record->getTimelineDescription();
                    }),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->showOnTabs(['draft']),
                TextColumn::make('start_date')
                    ->label('First invoice')
                    ->date()
                    ->sortable()
                    ->showOnTabs(['draft']),
                TextColumn::make('last_date')
                    ->label('Last invoice')
                    ->date()
                    ->sortable()
                    ->hideOnTabs(['draft']),
                TextColumn::make('next_date')
                    ->label('Next invoice')
                    ->date()
                    ->sortable()
                    ->hideOnTabs(['draft']),
                TextColumn::make('total')
                    ->currencyWithConversion(static fn (RecurringInvoice $record) => $record->currency_code)
                    ->sortable()
                    ->toggleable()
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->hiddenOn(RecurringInvoicesRelationManager::class),
                SelectFilter::make('status')
                    ->options(RecurringInvoiceStatus::class)
                    ->native(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make()
                            ->url(static fn (RecurringInvoice $record): string => EditRecurringInvoice::getUrl(['record' => $record])),
                        ViewAction::make()
                            ->url(static fn (RecurringInvoice $record): string => ViewRecurringInvoice::getUrl(['record' => $record])),
                        RecurringInvoice::getManageScheduleAction(Action::class),
                    ])->dropdown(false),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListRecurringInvoices::route('/'),
            'create' => CreateRecurringInvoice::route('/create'),
            'view' => ViewRecurringInvoice::route('/{record}'),
            'edit' => EditRecurringInvoice::route('/{record}/edit'),
        ];
    }
}
