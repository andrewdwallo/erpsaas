<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Filament\Resources\CurrencyResource\RelationManagers;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use App\Services\CurrencyService;
use Closure;
use Exception;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Wallo\FilamentSelectify\Components\ToggleButton;

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
                    ->description('Upon selecting a currency code, the corresponding values based on real-world currencies will auto-populate. The default currency is used for all transactions and reports and cannot be deleted. Currency precision determines the number of decimal places to display when formatting currency amounts. The currency rate is set to 1 for the default currency and is utilized as the basis for setting exchange rates for all other currencies. Alterations to default values are allowed but manage such changes wisely as any confusion or discrepancies are your responsibility.')
                    ->schema([
                        Forms\Components\Select::make('code')
                            ->label('Code')
                            ->options(Currency::getCurrencyCodes())
                            ->searchable()
                            ->placeholder('Select a currency code...')
                            ->reactive()
                            ->hidden(static fn (Closure $get): bool => $get('enabled'))
                            ->afterStateUpdated(static function (Closure $set, $state) {
                                if ($state === null) {
                                    return;
                                }

                                $code = $state;
                                $currencyConfig = config("money.{$code}", []);
                                $currencyService = app(CurrencyService::class);

                                $defaultCurrency = Currency::getDefaultCurrency();

                                $rate = 1;

                                if ($defaultCurrency !== null) {
                                   $rate = $currencyService->getCachedExchangeRate($defaultCurrency, $code);
                                }

                                $set('name', $currencyConfig['name'] ?? '');
                                $set('rate', $rate);
                                $set('precision', $currencyConfig['precision'] ?? '');
                                $set('symbol', $currencyConfig['symbol'] ?? '');
                                $set('symbol_first', $currencyConfig['symbol_first'] ?? '');
                                $set('decimal_mark', $currencyConfig['decimal_mark'] ?? '');
                                $set('thousands_separator', $currencyConfig['thousands_separator'] ?? '');
                            })
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->hidden(static fn (Closure $get): bool => !$get('enabled'))
                            ->disabled(static fn (Closure $get): bool => $get('enabled'))
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->translateLabel()
                            ->maxLength(100)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->dehydrateStateUsing(static fn (Closure $get, $state) => $get('enabled') ? '1' : $state)
                            ->numeric()
                            ->reactive()
                            ->disabled(static fn (Closure $get): bool => $get('enabled'))
                            ->required(),
                        Forms\Components\Select::make('precision')
                            ->label('Precision')
                            ->searchable()
                            ->placeholder('Select the currency precision...')
                            ->options(['0', '1', '2', '3', '4'])
                            ->required(),
                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbol')
                            ->maxLength(5)
                            ->required(),
                        Forms\Components\Select::make('symbol_first')
                            ->label('Symbol Position')
                            ->searchable()
                            ->boolean('Before Amount', 'After Amount', 'Select the currency symbol position...')
                            ->required(),
                        Forms\Components\TextInput::make('decimal_mark')
                            ->label('Decimal Separator')
                            ->maxLength(1)
                            ->required(),
                        Forms\Components\TextInput::make('thousands_separator')
                            ->label('Thousands Separator')
                            ->maxLength(1)
                            ->required(),
                        ToggleButton::make('enabled')
                            ->label('Default Currency')
                            ->reactive()
                            ->offColor('danger')
                            ->onColor('primary')
                            ->afterStateUpdated(static function (Closure $set, Closure $get, $state) {
                                $enabled = $state;
                                $code = $get('code');
                                $currencyService = app(CurrencyService::class);

                                if ($enabled) {
                                    $rate = 1;
                                } else {
                                    $defaultCurrency = Currency::getDefaultCurrency();
                                    $rate = $defaultCurrency ? $currencyService->getCachedExchangeRate($defaultCurrency, $code) : 1;
                                }

                                $set('rate', $rate);
                            }),
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
