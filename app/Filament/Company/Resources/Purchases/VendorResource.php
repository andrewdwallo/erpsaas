<?php

namespace App\Filament\Company\Resources\Purchases;

use App\Enums\Accounting\BillStatus;
use App\Enums\Common\ContractorType;
use App\Enums\Common\VendorType;
use App\Filament\Company\Resources\Purchases\VendorResource\Pages;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\PhoneBuilder;
use App\Filament\Tables\Columns;
use App\Models\Common\Vendor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Group::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Vendor name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Radio::make('type')
                                    ->label('Vendor type')
                                    ->required()
                                    ->live()
                                    ->options(VendorType::class)
                                    ->default(VendorType::Regular)
                                    ->columnSpanFull(),
                                CreateCurrencySelect::make('currency_code')
                                    ->nullable()
                                    ->visible(static fn (Forms\Get $get) => VendorType::parse($get('type')) === VendorType::Regular),
                                Forms\Components\Select::make('contractor_type')
                                    ->label('Contractor type')
                                    ->required()
                                    ->live()
                                    ->visible(static fn (Forms\Get $get) => VendorType::parse($get('type')) === VendorType::Contractor)
                                    ->options(ContractorType::class),
                                Forms\Components\TextInput::make('ssn')
                                    ->label('Social security number')
                                    ->required()
                                    ->live()
                                    ->mask('999-99-9999')
                                    ->stripCharacters('-')
                                    ->maxLength(11)
                                    ->visible(static fn (Forms\Get $get) => ContractorType::parse($get('contractor_type')) === ContractorType::Individual)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('ein')
                                    ->label('Employer identification number')
                                    ->required()
                                    ->live()
                                    ->mask('99-9999999')
                                    ->stripCharacters('-')
                                    ->maxLength(10)
                                    ->visible(static fn (Forms\Get $get) => ContractorType::parse($get('contractor_type')) === ContractorType::Business)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('account_number')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('website')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->columnSpanFull(),
                            ]),
                        CustomSection::make('Primary Contact')
                            ->relationship('contact')
                            ->contained(false)
                            ->schema([
                                Forms\Components\Hidden::make('is_primary')
                                    ->default(true),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('First name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Last name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                PhoneBuilder::make('phones')
                                    ->hiddenLabel()
                                    ->blockLabels(false)
                                    ->default([
                                        ['type' => 'primary'],
                                    ])
                                    ->columnSpanFull()
                                    ->blocks([
                                        Forms\Components\Builder\Block::make('primary')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label('Phone')
                                                    ->required()
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('mobile')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label('Mobile')
                                                    ->required()
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('toll_free')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label('Toll free')
                                                    ->required()
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Forms\Components\Builder\Block::make('fax')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label('Fax')
                                                    ->live()
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                    ])
                                    ->deletable(fn (PhoneBuilder $builder) => $builder->getItemsCount() > 1)
                                    ->reorderable(false)
                                    ->blockNumbers(false)
                                    ->addActionLabel('Add Phone'),
                            ])->columns(),
                    ])->columns(1),
                Forms\Components\Section::make('Address Information')
                    ->relationship('address')
                    ->schema([
                        Forms\Components\Hidden::make('type')
                            ->default('general'),
                        AddressFields::make(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (Vendor $vendor) => $vendor->contact?->full_name),
                Tables\Columns\TextColumn::make('contact.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact.first_available_phone')
                    ->label('Phone')
                    ->state(static fn (Vendor $vendor) => $vendor->contact?->first_available_phone),
                Tables\Columns\TextColumn::make('address.address_string')
                    ->label('Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('payable_balance')
                    ->label('Payable balance')
                    ->getStateUsing(function (Vendor $vendor) {
                        return $vendor->bills()
                            ->unpaid()
                            ->get()
                            ->sumMoneyInDefaultCurrency('amount_due');
                    })
                    ->coloredDescription(function (Vendor $vendor) {
                        $overdue = $vendor->bills()
                            ->where('status', BillStatus::Overdue)
                            ->get()
                            ->sumMoneyInDefaultCurrency('amount_due');

                        if ($overdue <= 0) {
                            return null;
                        }

                        $formattedOverdue = CurrencyConverter::formatCentsToMoney($overdue);

                        return "Overdue: {$formattedOverdue}";
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query
                            ->withSum(['bills' => fn (Builder $query) => $query->unpaid()], 'amount_due')
                            ->orderBy('bills_sum_amount_due', $direction);
                    })
                    ->currency(convert: false)
                    ->alignEnd(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\ViewAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
