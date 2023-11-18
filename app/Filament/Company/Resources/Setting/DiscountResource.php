<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\DateFormat;
use App\Enums\DiscountComputation;
use App\Enums\DiscountScope;
use App\Enums\DiscountType;
use App\Enums\TimeFormat;
use App\Filament\Company\Resources\Setting\DiscountResource\Pages;
use App\Models\Setting\Discount;
use App\Models\Setting\Localization;
use App\Traits\NotifiesOnDelete;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Wallo\FilamentSelectify\Components\ToggleButton;

class DiscountResource extends Resource
{
    use NotifiesOnDelete;

    protected static ?string $model = Discount::class;

    protected static ?string $modelLabel = 'Discount';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/discounts';

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
                        Forms\Components\TextInput::make('name')
                            ->autofocus()
                            ->required()
                            ->localizeLabel()
                            ->maxLength(255)
                            ->rule(static function (Forms\Get $get, Forms\Components\Component $component): Closure {
                                return static function (string $attribute, $value, Closure $fail) use ($get, $component) {
                                    $existingDiscount = Discount::where('company_id', auth()->user()->currentCompany->id)
                                        ->where('name', $value)
                                        ->where('type', $get('type'))
                                        ->first();

                                    if ($existingDiscount && $existingDiscount->getKey() !== $component->getRecord()?->getKey()) {
                                        $message = translate('The :Type :record ":name" already exists.', [
                                            'Type' => $existingDiscount->type->getLabel(),
                                            'record' => strtolower(static::getModelLabel()),
                                            'name' => $value,
                                        ]);

                                        $fail($message);
                                    }
                                };
                            }),
                        Forms\Components\TextInput::make('description')
                            ->localizeLabel(),
                        Forms\Components\Select::make('computation')
                            ->localizeLabel()
                            ->options(DiscountComputation::class)
                            ->default(DiscountComputation::Percentage)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->localizeLabel()
                            ->rate(static fn (Forms\Get $get) => $get('computation'))
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->localizeLabel()
                            ->options(DiscountType::class)
                            ->default(DiscountType::Sales)
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->localizeLabel()
                            ->options(DiscountScope::class)
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->localizeLabel()
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
                            ->live()
                            ->localizeLabel()
                            ->minDate(static function (callable $get, ?Discount $record = null) {
                                $start_date = $get('start_date') ?? $record?->start_date;

                                return $start_date ?: today()->addDay();
                            })
                            ->maxDate(today()->addYear())
                            ->format('Y-m-d H:i:s')
                            ->displayFormat('F d, Y H:i')
                            ->seconds(false),
                        ToggleButton::make('enabled')
                            ->localizeLabel('Default')
                            ->onLabel(Discount::enabledLabel())
                            ->offLabel(Discount::disabledLabel()),
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
                    ->icon(static fn (Discount $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static function (Discount $record) {
                        $tooltipMessage = translate('Default :Type :Record', [
                            'Type' => $record->type->getLabel(),
                            'Record' => static::getModelLabel(),
                        ]);

                        return $record->isEnabled() ? $tooltipMessage : null;
                    })
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->localizeLabel()
                    ->rate(static fn (Discount $record) => $record->computation->value)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->localizeLabel()
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->localizeLabel()
                    ->formatStateUsing(static function (Discount $record) {
                        $dateFormat = Localization::firstOrFail()->date_format->value ?? DateFormat::DEFAULT;
                        $timeFormat = Localization::firstOrFail()->time_format->value ?? TimeFormat::DEFAULT;

                        return $record->start_date ? $record->start_date->format("{$dateFormat} {$timeFormat}") : 'N/A';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->localizeLabel()
                    ->formatStateUsing(static function (Discount $record) {
                        $dateFormat = Localization::firstOrFail()->date_format->value ?? DateFormat::DEFAULT;
                        $timeFormat = Localization::firstOrFail()->time_format->value ?? TimeFormat::DEFAULT;

                        return $record->end_date ? $record->end_date->format("{$dateFormat} {$timeFormat}") : 'N/A';
                    })
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
            ->checkIfRecordIsSelectableUsing(static function (Discount $record) {
                return $record->isDisabled();
            })
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
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
