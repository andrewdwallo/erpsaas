<?php

namespace App\Filament\Company\Resources\Setting;

use App\Facades\Forex;
use App\Filament\Company\Resources\Setting\CurrencyResource\Pages;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use App\Models\Setting\Currency as CurrencyModel;
use App\Traits\ChecksForeignKeyConstraints;
use App\Traits\NotifiesOnDelete;
use App\Utilities\Currency\CurrencyAccessor;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Wallo\FilamentSelectify\Components\ToggleButton;

class CurrencyResource extends Resource
{
    use ChecksForeignKeyConstraints;
    use NotifiesOnDelete;

    protected static ?string $model = CurrencyModel::class;

    protected static ?string $modelLabel = 'Currency';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/currencies';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Select::make('code')
                            ->options(CurrencyAccessor::getAvailableCurrencies())
                            ->searchable()
                            ->live()
                            ->required()
                            ->localizeLabel()
                            ->hidden(static fn (Forms\Get $get, $state): bool => $get('enabled') && $state !== null)
                            ->afterStateUpdated(static function (Forms\Set $set, $state) {
                                $fields = ['name', 'precision', 'symbol', 'symbol_first', 'decimal_mark', 'thousands_separator'];

                                if ($state === null) {
                                    array_walk($fields, static fn ($field) => $set($field, null));

                                    return;
                                }

                                $currencyDetails = CurrencyAccessor::getAllCurrencies()[$state] ?? [];
                                $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();
                                $exchangeRate = Forex::getCachedExchangeRate($defaultCurrencyCode, $state);

                                if ($exchangeRate !== null) {
                                    $set('rate', $exchangeRate);
                                }

                                array_walk($fields, static fn ($field) => $set($field, $currencyDetails[$field] ?? null));
                            }),
                        Forms\Components\TextInput::make('code')
                            ->localizeLabel()
                            ->hidden(static fn (Forms\Get $get): bool => ! ($get('enabled') && $get('code') !== null))
                            ->disabled(static fn (Forms\Get $get): bool => $get('enabled'))
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->localizeLabel()
                            ->maxLength(50)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->numeric()
                            ->rule('gt:0')
                            ->live()
                            ->localizeLabel()
                            ->disabled(static fn (?CurrencyModel $record): bool => $record?->isEnabled() ?? false)
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('precision')
                            ->localizeLabel()
                            ->options(['0', '1', '2', '3', '4'])
                            ->required(),
                        Forms\Components\TextInput::make('symbol')
                            ->localizeLabel()
                            ->maxLength(5)
                            ->required(),
                        Forms\Components\Select::make('symbol_first')
                            ->localizeLabel('Symbol Position')
                            ->boolean(translate('Before Amount'), translate('After Amount'), translate('Select a symbol position'))
                            ->required(),
                        Forms\Components\TextInput::make('decimal_mark')
                            ->localizeLabel('Decimal Separator')
                            ->maxLength(1)
                            ->rule(static function (Forms\Get $get): Closure {
                                return static function ($attribute, $value, Closure $fail) use ($get) {
                                    if ($value === $get('thousands_separator')) {
                                        $fail(translate('Separators must be unique.'));
                                    }
                                };
                            })
                            ->required(),
                        Forms\Components\TextInput::make('thousands_separator')
                            ->localizeLabel()
                            ->maxLength(1)
                            ->rule(static function (Forms\Get $get): Closure {
                                return static function ($attribute, $value, Closure $fail) use ($get) {
                                    if ($value === $get('decimal_mark')) {
                                        $fail(translate('Separators must be unique.'));
                                    }
                                };
                            })
                            ->nullable(),
                        ToggleButton::make('enabled')
                            ->localizeLabel('Default')
                            ->onLabel(CurrencyModel::enabledLabel())
                            ->offLabel(CurrencyModel::disabledLabel())
                            ->disabled(static fn (?CurrencyModel $record): bool => $record?->isEnabled() ?? false)
                            ->dehydrated()
                            ->live()
                            ->afterStateUpdated(static function (Forms\Set $set, Forms\Get $get, $state) {
                                $enabledState = (bool) $state;
                                $code = $get('code');

                                if (! $code) {
                                    return;
                                }

                                if ($enabledState) {
                                    $set('rate', 1);

                                    return;
                                }

                                $forexEnabled = Forex::isEnabled();
                                if ($forexEnabled) {
                                    $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();
                                    $exchangeRate = Forex::getCachedExchangeRate($defaultCurrencyCode, $code);
                                    if ($exchangeRate !== null) {
                                        $set('rate', $exchangeRate);
                                    }
                                }
                            }),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (CurrencyModel $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static function (CurrencyModel $record) {
                        $tooltipMessage = translate('Default :Record', [
                            'Record' => static::getModelLabel(),
                        ]);

                        return $record->isEnabled() ? $tooltipMessage : null;
                    })
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('symbol')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Currency $record) {
                        $modelsToCheck = [
                            Account::class,
                        ];

                        $isUsed = self::isForeignKeyUsed('currency_code', $record->code, $modelsToCheck);

                        if ($isUsed) {
                            $reason = 'in use';
                            self::notifyBeforeDelete($record, $reason);
                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(static function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            foreach ($records as $record) {
                                $modelsToCheck = [
                                    Account::class,
                                ];

                                $isUsed = self::isForeignKeyUsed('currency_code', $record->code, $modelsToCheck);

                                if ($isUsed) {
                                    $reason = 'in use';
                                    self::notifyBeforeDelete($record, $reason);
                                    $action->cancel();
                                }
                            }
                        }),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(static function (CurrencyModel $record) {
                return $record->isDisabled();
            })
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
