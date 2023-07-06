<?php

namespace App\Filament\Resources;

use App\Actions\Banking\CreateCurrencyFromAccount;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use Wallo\FilamentSelectify\Components\ButtonGroup;
use App\Models\Banking\Account;
use App\Models\Contact;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Squire\Models\Country;
use Squire\Models\Region;

class CustomerResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'customer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->customer()
            ->where('company_id', Auth::user()->currentCompany->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                ButtonGroup::make('entity')
                                    ->label('Entity')
                                    ->options([
                                        'individual' => 'Individual',
                                        'company' => 'Company',
                                    ])
                                    ->gridDirection('column')
                                    ->default('individual')
                                    ->columnSpan(1)
                                    ->required(),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->maxLength(100)
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->nullable(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\TextInput::make('website')
                                            ->label('Website')
                                            ->maxLength(100)
                                            ->url()
                                            ->nullable(),
                                        Forms\Components\TextInput::make('reference')
                                            ->label('Reference')
                                            ->maxLength(100)
                                            ->columnSpan(2)
                                            ->nullable(),
                                    ])->columnSpan(2),
                            ]),
                    ])->columns(),
                Forms\Components\Section::make('Billing')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Tax Number')
                            ->maxLength(100)
                            ->nullable(),
                        Forms\Components\Select::make('currency_code')
                            ->label('Currency')
                            ->relationship('currency', 'name', static fn (Builder $query) => $query->where('company_id', Auth::user()->currentCompany->id))
                            ->preload()
                            ->default(Account::getDefaultCurrencyCode())
                            ->searchable()
                            ->reactive()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\Select::make('currency.code')
                                    ->label('Code')
                                    ->searchable()
                                    ->options(Account::getCurrencyCodes())
                                    ->reactive()
                                    ->afterStateUpdated(static function (callable $set, $state) {
                                        $code = $state;
                                        $name = config("money.{$code}.name");
                                        $set('currency.name', $name);
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('currency.name')
                                    ->label('Name')
                                    ->maxLength(100)
                                    ->required(),
                                Forms\Components\TextInput::make('currency.rate')
                                    ->label('Rate')
                                    ->numeric()
                                    ->mask(static fn (Forms\Components\TextInput\Mask $mask) => $mask
                                        ->numeric()
                                        ->decimalPlaces(4)
                                        ->signed(false)
                                        ->padFractionalZeros(false)
                                        ->normalizeZeros(false)
                                        ->minValue(0.0001)
                                        ->maxValue(999999.9999)
                                        ->lazyPlaceholder(false))
                                    ->required(),
                            ])->createOptionAction(static function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->label('Add Currency')
                                    ->modalHeading('Add Currency')
                                    ->modalButton('Add')
                                    ->action(static function (array $data) {
                                        return DB::transaction(static function () use ($data) {
                                            $code = $data['currency']['code'];
                                            $name = $data['currency']['name'];
                                            $rate = $data['currency']['rate'];

                                            return (new CreateCurrencyFromAccount())->create($code, $name, $rate);
                                        });
                                    });
                            }),
                ])->columns(2),
                Forms\Components\Section::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Address')
                            ->maxLength(100)
                            ->columnSpanFull()
                            ->nullable(),
                        Forms\Components\Select::make('country')
                            ->label('Country')
                            ->searchable()
                            ->reactive()
                            ->options(Contact::getCountryOptions())
                            ->nullable(),
                        Forms\Components\Select::make('doesnt_exist') // TODO: Remove this when we have a better way to handle the searchable select when disabled
                            ->label('Province/State')
                            ->disabled()
                            ->hidden(static fn (callable $get) => $get('country') !== null),
                        Forms\Components\Select::make('state')
                            ->label('Province/State')
                            ->hidden(static fn (callable $get) => $get('country') === null)
                            ->options(static function (callable $get) {
                                $country = $get('country');

                                if (! $country) {
                                    return [];
                                }

                                return Contact::getRegionOptions($country);
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('city')
                            ->label('Town/City')
                            ->maxLength(100)
                            ->nullable(),
                        Forms\Components\TextInput::make('zip_code')
                            ->label('Postal/Zip Code')
                            ->maxLength(100)
                            ->nullable(),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->weight('semibold')
                    ->description(static fn (Contact $record) => $record->tax_number ?: 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->formatStateUsing(static fn (Contact $record) => $record->email ?: 'N/A')
                    ->description(static fn (Contact $record) => $record->phone ?: 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->searchable()
                    ->formatStateUsing(static fn (Contact $record) => $record->country ?: 'N/A')
                    ->description(static fn (Contact $record) => $record->currency->name ?: 'N/A')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
