<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Concerns\ChecksForeignKeyConstraints;
use App\Concerns\NotifiesOnDelete;
use App\Facades\Forex;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\CurrencyResource\Pages;
use App\Models\Accounting\Account;
use App\Models\Setting\Currency;
use App\Models\Setting\Currency as CurrencyModel;
use App\Utilities\Currency\CurrencyAccessor;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CurrencyResource extends Resource
{
    use ChecksForeignKeyConstraints;
    use NotifiesOnDelete;

    protected static ?string $model = CurrencyModel::class;

    protected static ?string $modelLabel = 'currency';

    protected static ?string $cluster = Settings::class;

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
                        Forms\Components\TextInput::make('name')
                            ->localizeLabel()
                            ->maxLength(50)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->numeric()
                            ->rule('gt:0')
                            ->live()
                            ->localizeLabel()
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
                            ->localizeLabel('Symbol position')
                            ->boolean(translate('Before amount'), translate('After amount'), translate('Select a symbol position'))
                            ->required(),
                        Forms\Components\TextInput::make('decimal_mark')
                            ->localizeLabel('Decimal separator')
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
                        $tooltipMessage = translate('Default :record', [
                            'record' => static::getModelLabel(),
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
                        })
                        ->hidden(function (Table $table) {
                            return $table->getAllSelectableRecordsCount() === 0;
                        }),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(static function (CurrencyModel $record) {
                return $record->isDisabled();
            });
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
