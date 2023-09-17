<?php

namespace App\Filament\Company\Resources\Setting;

use App\Filament\Company\Resources\Setting\CurrencyResource\Pages;
use App\Filament\Company\Resources\Setting\CurrencyResource\RelationManagers;
use App\Models\Setting\Currency;
use App\Services\CurrencyService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Wallo\FilamentSelectify\Components\ButtonGroup;
use Wallo\FilamentSelectify\Components\ToggleButton;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/currencies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Select::make('code')
                        ->label('Code')
                        ->options(Currency::getAvailableCurrencyCodes())
                        ->searchable()
                        ->placeholder('Select a currency code...')
                        ->live()
                        ->required()
                        ->hidden(static fn (Forms\Get $get): bool => $get('enabled'))
                        ->afterStateUpdated(static function (Forms\Set $set, $state) {
                            if ($state === null) {
                                return;
                            }

                            $code = $state;

                            $allCurrencies = Currency::getAllCurrencies();

                            $selectedCurrencyCode = $allCurrencies[$code] ?? [];

                            $currencyService = app(CurrencyService::class);
                            $defaultCurrencyCode = Currency::getDefaultCurrencyCode();
                            $rate = 1;

                            if ($defaultCurrencyCode !== null) {
                                $rate = $currencyService->getCachedExchangeRate($defaultCurrencyCode, $code);
                            }

                            $set('name', $selectedCurrencyCode['name'] ?? '');
                            $set('rate', $rate);
                            $set('precision', $selectedCurrencyCode['precision'] ?? '');
                            $set('symbol', $selectedCurrencyCode['symbol'] ?? '');
                            $set('symbol_first', $selectedCurrencyCode['symbol_first'] ?? '');
                            $set('decimal_mark', $selectedCurrencyCode['decimal_mark'] ?? '');
                            $set('thousands_separator', $selectedCurrencyCode['thousands_separator'] ?? '');
                        }),
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->hidden(static fn (Forms\Get $get): bool => !$get('enabled'))
                            ->disabled(static fn (Forms\Get $get): bool => $get('enabled'))
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->maxLength(50)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->dehydrateStateUsing(static fn (Forms\Get $get, $state): float => $get('enabled') ? '1.0' : (float) $state)
                            ->numeric()
                            ->live()
                            ->disabled(static fn (Forms\Get $get): bool => $get('enabled'))
                            ->required(),
                        Forms\Components\Select::make('precision')
                            ->label('Precision')
                            ->searchable()
                            ->placeholder('Select a precision...')
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
                            ->live()
                            ->offColor(Color::Red)
                            ->onColor(Color::Indigo)
                            ->afterStateUpdated(static function (Forms\Set $set, Forms\Get $get, $state) {
                                $enabled = $state;
                                $code = $get('code');
                                $currencyService = app(CurrencyService::class);

                                if ($enabled) {
                                    $rate = 1;
                                } else {
                                    $defaultCurrencyCode = Currency::getDefaultCurrencyCode();
                                    $rate = $defaultCurrencyCode ? $currencyService->getCachedExchangeRate($defaultCurrencyCode, $code) : 1;
                                }

                                $set('rate', $rate);
                            }),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
