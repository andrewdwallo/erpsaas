<?php

namespace App\Filament\Company\Resources\Sales;

use App\Filament\Company\Resources\Sales\ClientResource\Pages\CreateClient;
use App\Filament\Company\Resources\Sales\ClientResource\Pages\EditClient;
use App\Filament\Company\Resources\Sales\ClientResource\Pages\ListClients;
use App\Filament\Company\Resources\Sales\ClientResource\Pages\ViewClient;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Filament\Forms\Components\CustomSection;
use App\Filament\Forms\Components\PhoneBuilder;
use App\Filament\Tables\Columns;
use App\Models\Common\Address;
use App\Models\Common\Client;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->schema([
                        Group::make()
                            ->columns()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Client name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('account_number')
                                    ->maxLength(255)
                                    ->columnStart(1),
                                TextInput::make('website')
                                    ->maxLength(255),
                                Textarea::make('notes')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Primary Contact')
                            ->relationship('primaryContact')
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
                        Repeater::make('secondaryContacts')
                            ->relationship()
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->hiddenLabel()
                            ->extraAttributes([
                                'class' => 'uncontained',
                            ])
                            ->columns()
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->itemLabel(function (Repeater $component, array $state): ?string {
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
                                TextInput::make('first_name')
                                    ->label('First name')
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                TextInput::make('last_name')
                                    ->label('Last name')
                                    ->live(onBlur: true)
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                PhoneBuilder::make('phones')
                                    ->hiddenLabel()
                                    ->blockLabels(false)
                                    ->default([
                                        ['type' => 'primary'],
                                    ])
                                    ->blocks([
                                        Block::make('primary')
                                            ->schema([
                                                TextInput::make('number')
                                                    ->label('Phone')
                                                    ->maxLength(255),
                                            ])->maxItems(1),
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->blockNumbers(false),
                            ]),
                    ])->columns(1),
                Section::make('Billing')
                    ->schema([
                        CreateCurrencySelect::make('currency_code')
                            ->softRequired(),
                        CustomSection::make('Billing Address')
                            ->relationship('billingAddress')
                            ->saveRelationshipsUsing(null)
                            ->saveRelationshipsBeforeChildrenUsing(null)
                            ->dehydrated(true)
                            ->contained(false)
                            ->schema([
                                Hidden::make('type')
                                    ->default('billing'),
                                AddressFields::make(),
                            ])->columns(),
                    ])
                    ->columns(1),
                Section::make('Shipping')
                    ->relationship('shippingAddress')
                    ->saveRelationshipsUsing(null)
                    ->saveRelationshipsBeforeChildrenUsing(null)
                    ->dehydrated(true)
                    ->schema([
                        Hidden::make('type')
                            ->default('shipping'),
                        TextInput::make('recipient')
                            ->label('Recipient')
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->maxLength(255),
                        CustomSection::make('Shipping Address')
                            ->contained(false)
                            ->schema([
                                Checkbox::make('same_as_billing')
                                    ->label('Same as billing address')
                                    ->live()
                                    ->afterStateHydrated(function (?Address $record, Checkbox $component) {
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
                                            'country_code',
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
                                Textarea::make('notes')
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(static fn (Client $client) => $client->primaryContact?->full_name),
                TextColumn::make('primaryContact.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('primaryContact.phones')
                    ->label('Phone')
                    ->toggleable()
                    ->state(static fn (Client $client) => $client->primaryContact?->first_available_phone),
                TextColumn::make('billingAddress.address_string')
                    ->label('Billing address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->listWithLineBreaks(),
                TextColumn::make('balance')
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
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
