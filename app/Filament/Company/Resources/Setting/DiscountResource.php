<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\{DiscountComputation, DiscountScope, DiscountType};
use App\Filament\Company\Resources\Setting\DiscountResource\Pages;
use App\Models\Setting\Discount;
use Closure;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\{Forms, Tables};
use Wallo\FilamentSelectify\Components\ToggleButton;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/discounts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->autofocus()
                            ->required()
                            ->maxLength(255)
                            ->rule(static function (Forms\Get $get, Forms\Components\Component $component): Closure {
                                return static function (string $attribute, $value, Closure $fail) use ($get, $component) {
                                    $existingCategory = Discount::where('company_id', auth()->user()->currentCompany->id)
                                        ->where('name', $value)
                                        ->where('type', $get('type'))
                                        ->first();

                                    if ($existingCategory && $existingCategory->getKey() !== $component->getRecord()?->getKey()) {
                                        $type = $get('type')->getLabel();
                                        $fail("The {$type} discount \"{$value}\" already exists.");
                                    }
                                };
                            }),
                        Forms\Components\TextInput::make('description')
                            ->label('Description'),
                        Forms\Components\Select::make('computation')
                            ->label('Computation')
                            ->options(DiscountComputation::class)
                            ->default(DiscountComputation::Percentage)
                            ->live()
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->numeric()
                            ->suffix(static function (Forms\Get $get) {
                                $computation = $get('computation');

                                if ($computation === DiscountComputation::Percentage) {
                                    return '%';
                                }

                                return null;
                            })
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(DiscountType::class)
                            ->default(DiscountType::Sales)
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->label('Scope')
                            ->options(DiscountScope::class)
                            ->native(false),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Start Date')
                            ->native(false)
                            ->minDate(static function ($context, ?Discount $record = null) {
                                if ($context === 'create') {
                                    return today()->addDay();
                                }

                                return $record?->start_date?->isFuture() ? today()->addDay() : $record?->start_date;
                            })
                            ->maxDate(static function (callable $get, ?Discount $record = null) {
                                $end_date = $get('end_date') ?? $record?->end_date;

                                return $end_date ?: today()->addYear();
                            })
                            ->format('Y-m-d H:i:s')
                            ->displayFormat('F d, Y H:i')
                            ->seconds(false)
                            ->live()
                            ->disabled(static fn ($context, ?Discount $record = null) => $context === 'edit' && $record?->start_date?->isPast() ?? false)
                            ->helperText(static fn (Forms\Components\DateTimePicker $component) => $component->isDisabled() ? 'Start date cannot be changed after the discount has begun.' : null),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('End Date')
                            ->native(false)
                            ->live()
                            ->minDate(static function (callable $get, ?Discount $record = null) {
                                $start_date = $get('start_date') ?? $record?->start_date;

                                return $start_date ?: today()->addDay();
                            })
                            ->maxDate(today()->addYear())
                            ->format('Y-m-d H:i:s')
                            ->displayFormat('F d, Y H:i')
                            ->seconds(false),
                        ToggleButton::make('enabled')
                            ->label('Default'),
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
                    ->tooltip(static fn (Discount $record) => $record->enabled ? "Default {$record->type->getLabel()} Discount" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation')
                    ->label('Computation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->formatStateUsing(static fn (Discount $record) => $record->rate . ($record->computation === DiscountComputation::Percentage ? '%' : null))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->formatStateUsing(static fn (Discount $record) => $record->start_date ? $record->start_date->format('F d, Y H:i') : 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->formatStateUsing(static fn (Discount $record) => $record->end_date ? $record->end_date->format('F d, Y H:i') : 'N/A')
                    ->color(static fn (Discount $record) => $record->end_date?->isPast() ? 'danger' : null)
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
