<?php

namespace App\Filament\Company\Clusters\Settings\Resources;

use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\AdjustmentScope;
use App\Enums\Accounting\AdjustmentType;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Company\Clusters\Settings\Resources\AdjustmentResource\Pages;
use App\Models\Accounting\Adjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Wallo\FilamentSelectify\Components\ToggleButton;

class AdjustmentResource extends Resource
{
    protected static ?string $model = Adjustment::class;

    protected static ?string $cluster = Settings::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->autofocus()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->autosize(),
                    ]),
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->localizeLabel()
                            ->options(AdjustmentCategory::class)
                            ->default(AdjustmentCategory::Tax)
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->localizeLabel()
                            ->options(AdjustmentType::class)
                            ->default(AdjustmentType::Sales)
                            ->live()
                            ->required(),
                        ToggleButton::make('recoverable')
                            ->label('Recoverable')
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => AdjustmentCategory::parse($get('category'))->isTax() && AdjustmentType::parse($get('type'))->isPurchase()),
                    ])
                    ->columns()
                    ->visibleOn('create'),
                Forms\Components\Section::make('Adjustment Details')
                    ->schema([
                        Forms\Components\Select::make('computation')
                            ->localizeLabel()
                            ->options(AdjustmentComputation::class)
                            ->default(AdjustmentComputation::Percentage)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->localizeLabel()
                            ->rate(static fn (Forms\Get $get) => $get('computation'))
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->localizeLabel()
                            ->options(AdjustmentScope::class),
                    ])
                    ->columns(),
                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date'),
                        Forms\Components\DateTimePicker::make('end_date'),
                    ])
                    ->columns()
                    ->visible(fn (Forms\Get $get) => AdjustmentCategory::parse($get('category'))->isDiscount()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rate')
                    ->localizeLabel()
                    ->rate(static fn (Adjustment $record) => $record->computation->value)
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
            'index' => Pages\ListAdjustments::route('/'),
            'create' => Pages\CreateAdjustment::route('/create'),
            'edit' => Pages\EditAdjustment::route('/{record}/edit'),
        ];
    }
}
