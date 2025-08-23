<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Facades\Forex;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\CurrencyResource\Pages\CreateCurrency;
use App\Filament\Company\Clusters\Settings\Resources\CurrencyResource\Pages\EditCurrency;
use App\Filament\Company\Clusters\Settings\Resources\CurrencyResource\Pages\ListCurrencies;
use App\Models\Setting\Currency as CurrencyModel;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = CurrencyModel::class;

    protected static ?string $modelLabel = 'currency';

    protected static ?string $cluster = Settings::class;

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        Select::make('code')
                            ->options(CurrencyAccessor::getAvailableCurrencies())
                            ->searchable()
                            ->live()
                            ->required()
                            ->localizeLabel()
                            ->disabledOn('edit')
                            ->afterStateUpdated(static function (Set $set, $state) {
                                if (! $state) {
                                    return;
                                }

                                $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();
                                $exchangeRate = Forex::getCachedExchangeRate($defaultCurrencyCode, $state);

                                if ($exchangeRate !== null) {
                                    $set('rate', $exchangeRate);
                                }
                            }),
                        TextInput::make('rate')
                            ->numeric()
                            ->rule('gt:0')
                            ->live()
                            ->localizeLabel()
                            ->required(),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
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
                TextColumn::make('code')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('symbol')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rate')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
