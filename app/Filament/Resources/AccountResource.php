<?php

namespace App\Filament\Resources;

use App\Actions\Banking\CreateCurrencyFromAccount;
use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Banking\Account;
use Closure;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Banking';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()->currentCompany->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Radio::make('type')
                            ->label('Type')
                            ->options(Account::getAccountTypes())
                            ->inline()
                            ->default('bank')
                            ->reactive()
                            ->afterStateUpdated(static fn (Closure $set, $state) => $state === 'card' ? $set('enabled', 'hidden'): $set('enabled', null))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->maxLength(100)
                            ->required(),
                        Forms\Components\TextInput::make('number')
                            ->label('Account Number')
                            ->unique(callback: static function (Unique $rule, $state) {
                                $companyId = Auth::user()->currentCompany->id;

                                return $rule->where('company_id', $companyId)->where('number', $state);
                            }, ignoreRecord: true)
                            ->maxLength(20)
                            ->required(),
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
                        Forms\Components\TextInput::make('opening_balance')
                            ->label('Opening Balance')
                            ->required()
                            ->default('0')
                            ->numeric()
                            ->mask(static fn (Forms\Components\TextInput\Mask $mask, Closure $get) => $mask
                                ->patternBlocks([
                                    'money' => static fn (Mask $mask) => $mask
                                        ->numeric()
                                        ->decimalPlaces(config('money.' . $get('currency_code') . '.precision'))
                                        ->decimalSeparator(config('money.' . $get('currency_code') . '.decimal_mark'))
                                        ->thousandsSeparator(config('money.' . $get('currency_code') . '.thousands_separator'))
                                        ->signed()
                                        ->padFractionalZeros()
                                        ->normalizeZeros(false),
                            ])
                            ->pattern(config('money.' . $get('currency_code') . '.symbol_first') ? config('money.' . $get('currency_code') . '.symbol') . 'money' : 'money' . config('money.' . $get('currency_code') . '.symbol'))
                            ->lazyPlaceholder(false)),
                        Forms\Components\Toggle::make('enabled')
                            ->hidden(fn (Closure $get) => $get('type') === 'card')
                            ->label('Default Account'),
                    ])->columns(),
                Forms\Components\Section::make('Bank')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('bank_phone')
                            ->label('Bank Phone')
                            ->mask(static fn (Forms\Components\TextInput\Mask $mask) => $mask->pattern('(000) 000-0000'))
                            ->maxLength(20),
                        Forms\Components\Textarea::make('bank_address')
                            ->label('Bank Address')
                            ->columnSpanFull(),
                        ])->columns(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold')
                    ->icon(static fn (Account $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Account $record) => $record->enabled ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Account Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_phone')
                    ->label('Phone')
                    ->formatStateUsing(static fn ($record) => ($record->bank_phone !== '') ? vsprintf('(%d%d%d) %d%d%d-%d%d%d%d', str_split($record->bank_phone)) : '-'),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Current Balance')
                    ->sortable()
                    ->money(static fn ($record) => $record->currency_code, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(static function (Tables\Actions\DeleteAction $action, Account $record) {
                        if ($record->enabled) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name account is currently set as your default account and cannot be deleted. Please set a different account as your default before attempting to delete this one.', ['name' => $record->name]))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(static function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                        foreach ($records as $record) {
                            if ($record->enabled) {
                                Notification::make()
                                    ->danger()
                                    ->title('Action Denied')
                                    ->body(__('The :name account is currently set as your default account and cannot be deleted. Please set a different account as your default before attempting to delete this one.', ['name' => $record->name]))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }
                    }),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }    
}
