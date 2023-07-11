<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Filament\Resources\DiscountResource\RelationManagers;
use App\Models\Setting\Discount;
use Filament\Forms;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentSelectify\Components\ToggleButton;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Settings';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Auth::user()->currentCompany->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->required(),
                    Forms\Components\TextInput::make('description')
                        ->label('Description'),
                    Forms\Components\Select::make('computation')
                        ->label('Computation')
                        ->options(Discount::getComputationTypes())
                        ->reactive()
                        ->searchable()
                        ->default('percentage')
                        ->required(),
                    Forms\Components\TextInput::make('rate')
                        ->label('Rate')
                        ->mask(static fn (Mask $mask) => $mask
                            ->numeric()
                            ->decimalPlaces(4)
                            ->decimalSeparator('.')
                            ->thousandsSeparator(',')
                            ->minValue(0)
                            ->normalizeZeros()
                            ->padFractionalZeros()
                        )
                        ->suffix(static fn (callable $get) => $get('computation') === 'percentage' ? '%' : null)
                        ->default(0.0000)
                        ->required(),
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(Discount::getDiscountTypes())
                        ->searchable()
                        ->default('sales')
                        ->required(),
                    Forms\Components\Select::make('scope')
                        ->label('Scope')
                        ->options(Discount::getDiscountScopes())
                        ->searchable(),
                    Forms\Components\DateTimePicker::make('start_date')
                        ->label('Start Date')
                        ->minDate(static function ($context, Discount|null $record = null) {
                            if ($context === 'create') {
                                return today()->addDay();
                            }

                            return $record?->start_date->isFuture() ? today()->addDay() : $record?->start_date;
                        })
                        ->maxDate(static function (callable $get, Discount|null $record = null) {
                            $end_date = $get('end_date') ?? $record?->end_date;

                            return $end_date ?: today()->addYear();
                        })
                        ->format('Y-m-d H:i:s')
                        ->displayFormat('F d, Y H:i')
                        ->withoutSeconds()
                        ->reactive()
                        ->disabled(static fn ($context, Discount|null $record = null) => $context === 'edit' && $record?->start_date->isPast())
                        ->helperText(static fn (Forms\Components\DateTimePicker $component) => $component->isDisabled() ? 'Start date cannot be changed after the discount has begun.' : null),
                    Forms\Components\DateTimePicker::make('end_date')
                        ->label('End Date')
                        ->reactive()
                        ->minDate(static function (callable $get, Discount|null $record = null) {
                            $start_date = $get('start_date') ?? $record?->start_date;

                            return $start_date ?: today()->addDay();
                        })
                        ->maxDate(today()->addYear())
                        ->format('Y-m-d H:i:s')
                        ->displayFormat('F d, Y H:i')
                        ->withoutSeconds(),
                    ToggleButton::make('enabled')
                        ->label('Default')
                        ->offColor('danger')
                        ->onColor('primary'),
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
                    ->icon(static fn (Discount $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Discount $record) => $record->enabled ? "Default ". ucwords($record->type) . " Discount" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation')
                    ->label('Computation')
                    ->formatStateUsing(static fn (Discount $record) => ucwords($record->computation))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->formatStateUsing(static function (Discount $record) {
                        $rate = $record->rate;

                        return $rate . ($record->computation === 'percentage' ? '%' : null);
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(static fn (Discount $record) => ucwords($record->type))
                    ->colors([
                        'success' => 'sales',
                        'warning' => 'purchase',
                        'secondary' => 'none',
                    ])
                    ->icons([
                        'heroicon-o-cash' => 'sales',
                        'heroicon-o-shopping-bag' => 'purchase',
                        'heroicon-o-x-circle' => 'none',
                    ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                ->label('Start Date')
                ->formatStateUsing(static fn (Discount $record) => $record->start_date ? $record->start_date->format('F d, Y H:i') : null)
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                ->label('End Date')
                ->formatStateUsing(static fn (Discount $record) => $record->end_date ? $record->end_date->format('F d, Y H:i') : null)
                ->color(static fn(Discount $record) => $record->end_date?->isPast() ? 'danger' : null)
                ->searchable()
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Create a cron job to update recurring discounts once they have expired
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
