<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ClientResource\Pages;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\PhoneBuilder;
use App\Filament\Tables\Columns;
use App\Models\Common\Address;
use App\Models\Common\Client;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Group::make()
                            ->columns()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Client name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('account_number')
                                    ->maxLength(255)
                                    ->columnStart(1),
                                Forms\Components\TextInput::make('website')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->columnSpanFull(),
                            ]),
                        CustomSection::make('Primary Contact')
                            ->relationship('primaryContact')
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
                        Forms\Components\Repeater::make('secondaryContacts')
                            ->relationship()
                            ->hiddenLabel()
                            ->extraAttributes([
                                'class' => 'uncontained',
                            ])
                            ->columns()
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->itemLabel(function (Forms\Components\Repeater $component, array $state): ?string {
                                if ($component->getItemsCount() === 1) {
                                    return 'Secondary Contact';
                                }

                                $firstName = $state['first_name'] ?? null;
                                $lastName = $state['last_name'] ?? null;

                                if ($firstName && $lastName) {
                                    return "{$firstName} {$lastName}";
                                }

                                if ($firstName) {
                                    return $firstName;
                                }

                                return 'Secondary Contact';
                            })
                            ->addActionLabel('Add Contact')
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('First name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Last name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255),
                                PhoneBuilder::make('phones')
                                    ->hiddenLabel()
                                    ->blockLabels(false)
                                    ->default([
                                        ['type' => 'primary'],
                                    ])
                                    ->blocks([
                                        Forms\Components\Builder\Block::make('primary')
                                            ->schema([
                                                Forms\Components\TextInput::make('number')
                                                    ->label('Phone')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])->maxItems(1),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->blockNumbers(false),
                            ]),
                    ])->columns(1),
                Forms\Components\Section::make('Billing')
                    ->schema([
                        CreateCurrencySelect::make('currency_code'),
                        CustomSection::make('Billing Address')
                            ->relationship('billingAddress')
                            ->saveRelationshipsUsing(null)
                            ->dehydrated(true)
                            ->contained(false)
                            ->schema([
                                Forms\Components\Hidden::make('type')
                                    ->default('billing'),
                                AddressFields::make(),
                            ])->columns(),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('Shipping')
                    ->relationship('shippingAddress')
                    ->saveRelationshipsUsing(null)
                    ->dehydrated(true)
                    ->schema([
                        Forms\Components\Hidden::make('type')
                            ->default('shipping'),
                        Forms\Components\TextInput::make('recipient')
                            ->label('Recipient')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->maxLength(255),
                        CustomSection::make('Shipping Address')
                            ->contained(false)
                            ->schema([
                                Forms\Components\Checkbox::make('same_as_billing')
                                    ->label('Same as billing address')
                                    ->live()
                                    ->afterStateHydrated(function (?Address $record, Forms\Components\Checkbox $component) {
                                        if (! $record || $record->parent_address_id) {
                                            return $component->state(true);
                                        }

                                        return $component->state(false);
                                    })
                                    ->afterStateUpdated(static function (Get $get, Set $set, $state) {
                                        if ($state) {
                                            return;
                                        }

                                        $billingAddress = $get('../billingAddress');

                                        $fieldsToSync = [
                                            'address_line_1',
                                            'address_line_2',
                                            'country',
                                            'state_id',
                                            'city',
                                            'postal_code',
                                        ];

                                        foreach ($fieldsToSync as $field) {
                                            $set($field, $billingAddress[$field]);
                                        }
                                    })
                                    ->columnSpanFull(),
                                AddressFields::make()
                                    ->visible(static fn (Get $get) => ! $get('same_as_billing')),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Delivery instructions')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ])->columns(),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns::id(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (Client $client) => $client->primaryContact->full_name),
                Tables\Columns\TextColumn::make('primaryContact.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('primaryContact.phones')
                    ->label('Phone')
                    ->toggleable()
                    ->state(static fn (Client $client) => $client->primaryContact->first_available_phone),
                Tables\Columns\TextColumn::make('billingAddress.address_string')
                    ->label('Billing address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->getStateUsing(function (Client $client) {
                        return $client->invoices()
                            ->unpaid()
                            ->get()
                            ->sumMoneyInDefaultCurrency('amount_due');
                    })
                    ->coloredDescription(function (Client $client) {
                        $overdue = $client->invoices()
                            ->overdue()
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
                            ->withSum(['invoices' => fn (Builder $query) => $query->unpaid()], 'amount_due')
                            ->orderBy('invoices_sum_amount_due', $direction);
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
