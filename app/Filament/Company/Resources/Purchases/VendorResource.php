<?php

namespace App\Filament\Company\Resources\Purchases;

use App\Enums\Accounting\BillStatus;
use App\Enums\Common\ContractorType;
use App\Enums\Common\VendorType;
use App\Filament\Company\Resources\Purchases\VendorResource\Pages\CreateVendor;
use App\Filament\Company\Resources\Purchases\VendorResource\Pages\EditVendor;
use App\Filament\Company\Resources\Purchases\VendorResource\Pages\ListVendors;
use App\Filament\Company\Resources\Purchases\VendorResource\Pages\ViewVendor;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\PhoneBuilder;
use App\Filament\Tables\Columns;
use App\Models\Common\Vendor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Vendor name')
                                    ->required()
                                    ->maxLength(255),
                                Radio::make('type')
                                    ->label('Vendor type')
                                    ->required()
                                    ->live()
                                    ->options(VendorType::class)
                                    ->default(VendorType::Regular)
                                    ->columnSpanFull(),
                                CreateCurrencySelect::make('currency_code')
                                    ->softRequired()
                                    ->visible(static fn (Get $get) => VendorType::parse($get('type')) === VendorType::Regular),
                                Select::make('contractor_type')
                                    ->label('Contractor type')
                                    ->required()
                                    ->live()
                                    ->visible(static fn (Get $get) => VendorType::parse($get('type')) === VendorType::Contractor)
                                    ->options(ContractorType::class),
                                TextInput::make('ssn')
                                    ->label('Social security number')
                                    ->required()
                                    ->live()
                                    ->mask('999-99-9999')
                                    ->stripCharacters('-')
                                    ->maxLength(11)
                                    ->visible(static fn (Get $get) => ContractorType::parse($get('contractor_type')) === ContractorType::Individual)
                                    ->maxLength(255),
                                TextInput::make('ein')
                                    ->label('Employer identification number')
                                    ->required()
                                    ->live()
                                    ->mask('99-9999999')
                                    ->stripCharacters('-')
                                    ->maxLength(10)
                                    ->visible(static fn (Get $get) => ContractorType::parse($get('contractor_type')) === ContractorType::Business)
                                    ->maxLength(255),
                                TextInput::make('account_number')
                                    ->maxLength(255),
                                TextInput::make('website')
                                    ->maxLength(255),
                                Textarea::make('notes')
                                    ->columnSpanFull(),
                            ]),
                        CustomSection::make('Primary Contact')
                            ->relationship('contact')
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->contained(false)
                            ->schema([
                                Hidden::make('is_primary')
                                    ->default(true),
                                TextInput::make('first_name')
                                    ->label('First name')
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('Last name')
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
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
                                        Block::make('primary')
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label('Phone')
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Block::make('mobile')
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label('Mobile')
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Block::make('toll_free')
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label('Toll free')
                                                    ->maxLength(15),
                                            ])->maxItems(1),
                                        Block::make('fax')
                                            ->schema([
                                                TextInput::make('number')
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
                Section::make('Address Information')
                    ->relationship('address')
                    ->saveRelationshipsUsing(null)
                    ->saveRelationshipsBeforeChildrenUsing(null)
                    ->dehydrated(true)
                    ->schema([
                        Hidden::make('type')
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
                TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (Vendor $vendor) => $vendor->contact?->full_name),
                TextColumn::make('contact.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('contact.first_available_phone')
                    ->label('Phone')
                    ->state(static fn (Vendor $vendor) => $vendor->contact?->first_available_phone),
                TextColumn::make('address.address_string')
                    ->label('Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->listWithLineBreaks(),
                TextColumn::make('payable_balance')
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
            ->recordActions([
                ActionGroup::make([
                    ActionGroup::make([
                        EditAction::make(),
                        ViewAction::make(),
                    ])->dropdown(false),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                //
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
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'view' => ViewVendor::route('/{record}'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
