<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use Closure;
use Exception;
use Filament\Forms;
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

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Settings';

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
                        Forms\Components\Select::make('code')
                            ->label('Code')
                            ->options(Currency::getCurrencyCodes())
                            ->searchable()
                            ->placeholder('- Select Code -')
                            ->reactive()
                            ->afterStateUpdated(static function (Closure $set, $state) {
                                $code = $state;
                                $name = config("money.{$code}.name");
                                $precision = config("money.{$code}.precision");
                                $symbol = config("money.{$code}.symbol");
                                $symbol_first = config("money.{$code}.symbol_first");
                                $decimal_mark = config("money.{$code}.decimal_mark");
                                $thousands_separator = config("money.{$code}.thousands_separator");

                                $set('name', $name);
                                $set('precision', $precision);
                                $set('symbol', $symbol);
                                $set('symbol_first', $symbol_first);
                                $set('decimal_mark', $decimal_mark);
                                $set('thousands_separator', $thousands_separator);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->translateLabel()
                            ->placeholder('Enter Name')
                            ->maxLength(100)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->placeholder('Enter Rate')
                            ->dehydrateStateUsing(static fn (Closure $get, $state): bool => $get('enabled') === true ? '1' : $state) // rate is 1 when enabled is true
                            ->numeric()
                            ->disabled(static fn (Closure $get): bool => $get('enabled') === true) // disabled is true when enabled is true
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
                        Forms\Components\Select::make('precision')
                            ->label('Precision')
                            ->searchable()
                            ->placeholder('- Select Precision -')
                            ->options(['0', '1', '2', '3', '4'])
                            ->required(),
                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbol')
                            ->placeholder('Enter Symbol')
                            ->maxLength(5)
                            ->required(),
                        Forms\Components\Select::make('symbol_first')
                            ->label('Symbol Position')
                            ->searchable()
                            ->boolean('Before Amount', 'After Amount', '- Select Symbol Position -')
                            ->required(),
                        Forms\Components\TextInput::make('decimal_mark')
                            ->label('Decimal Separator')
                            ->placeholder('Enter Decimal Separator')
                            ->maxLength(1)
                            ->required(),
                        Forms\Components\TextInput::make('thousands_separator')
                            ->label('Thousands Separator')
                            ->placeholder('Enter Thousands Separator')
                            ->maxLength(1)
                            ->required(),
                        Forms\Components\Toggle::make('enabled')
                            ->label('Default Currency')
                            ->reactive()
                            ->inline()
                            ->afterStateUpdated(static fn (Closure $set, $state) => $state ? $set('rate', '1') : $set('rate', null))
                            ->default(false),
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
                    ->label('Name')
                    ->weight('semibold')
                    ->icon(static fn (Currency $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Currency $record) => $record->enabled ? 'Default Currency' : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->formatStateUsing(static fn ($state) => str_contains($state, '.') ? rtrim(rtrim($state, '0'), '.') : null)
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(static function (Tables\Actions\DeleteAction $action, Currency $record) {
                        $defaultCurrency = $record->enabled;
                        $accountUsesCurrency = Account::where('currency_code', $record->code)->exists();

                        if ($defaultCurrency) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name currency is currently set as the default currency and cannot be deleted. Please set a different currency as your default before attempting to delete this one.', ['name' => $record->name]))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        } elseif ($accountUsesCurrency) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name currency is currently in use by one or more accounts and cannot be deleted. Please remove this currency from all accounts before attempting to delete it.', ['name' => $record->name]))
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
                            $defaultCurrency = $record->enabled;
                            $accountUsesCurrency = Account::where('currency_code', $record->code)->exists();

                            if ($defaultCurrency) {
                                Notification::make()
                                    ->danger()
                                    ->title('Action Denied')
                                    ->body(__('The :name currency is currently set as the default currency and cannot be deleted. Please set a different currency as your default before attempting to delete this one.', ['name' => $record->name]))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            } elseif ($accountUsesCurrency) {
                                Notification::make()
                                    ->danger()
                                    ->title('Action Denied')
                                    ->body(__('The :name currency is currently in use by one or more accounts and cannot be deleted. Please remove this currency from all accounts before attempting to delete it.', ['name' => $record->name]))
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
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }    
}
