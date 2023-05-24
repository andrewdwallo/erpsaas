<?php

namespace App\Filament\Resources;

use App\Actions\Banking\CreateCurrencyFromAccount;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'customer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'customer')
            ->where('company_id', Auth::user()->currentCompany->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Radio::make('entity')
                            ->options([
                                'company' => 'Company',
                                'individual' => 'Individual',
                            ])
                            ->inline()
                            ->default('company')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->maxLength(100)
                            ->placeholder('Enter Name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->placeholder('Enter Email')
                            ->nullable(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->placeholder('Enter Phone')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('website')
                            ->maxLength(100)
                            ->prefix('https://')
                            ->placeholder('Enter Website')
                            ->nullable(),
                        Forms\Components\TextInput::make('reference')
                            ->maxLength(100)
                            ->placeholder('Enter Reference')
                            ->nullable(),
                    ])->columns(2),
                Forms\Components\Section::make('Billing')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->maxLength(100)
                            ->placeholder('Enter Tax Number')
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
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->maxLength(100)
                            ->placeholder('Enter Address')
                            ->columnSpanFull()
                            ->nullable(),
                        Forms\Components\TextInput::make('city')
                            ->label('Town/City')
                            ->maxLength(100)
                            ->placeholder('Enter Town/City')
                            ->nullable(),
                        Forms\Components\TextInput::make('zip_code')
                            ->label('Postal/Zip Code')
                            ->maxLength(100)
                            ->placeholder('Enter Postal/Zip Code')
                            ->nullable(),
                        Forms\Components\TextInput::make('state')
                            ->label('Province/State')
                            ->maxLength(100)
                            ->placeholder('Enter Province/State')
                            ->required(),
                        Forms\Components\TextInput::make('country')
                            ->label('Country')
                            ->maxLength(100)
                            ->placeholder('Enter Country')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.name')
                    ->searchable()
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
