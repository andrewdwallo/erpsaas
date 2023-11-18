<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\TaxComputation;
use App\Enums\TaxScope;
use App\Enums\TaxType;
use App\Filament\Company\Resources\Setting\TaxResource\Pages;
use App\Models\Setting\Tax;
use App\Traits\NotifiesOnDelete;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Wallo\FilamentSelectify\Components\ToggleButton;

class TaxResource extends Resource
{
    use NotifiesOnDelete;

    protected static ?string $model = Tax::class;

    protected static ?string $modelLabel = 'Tax';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/taxes';

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
                                    $existingTax = Tax::where('company_id', auth()->user()->currentCompany->id)
                                        ->where('name', $value)
                                        ->where('type', $get('type'))
                                        ->first();

                                    if ($existingTax && $existingTax->getKey() !== $component->getRecord()?->getKey()) {
                                        $message = translate('The :Type :record ":name" already exists.', [
                                            'Type' => $existingTax->type->getLabel(),
                                            'record' => strtolower(static::getModelLabel()),
                                            'name' => $value,
                                        ]);

                                        $fail($message);
                                    }
                                };
                            }),
                        Forms\Components\TextInput::make('description'),
                        Forms\Components\Select::make('computation')
                            ->localizeLabel()
                            ->options(TaxComputation::class)
                            ->default(TaxComputation::Percentage)
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->localizeLabel()
                            ->rate(static fn (Forms\Get $get) => $get('computation'))
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->localizeLabel()
                            ->options(TaxType::class)
                            ->default(TaxType::Sales)
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->localizeLabel()
                            ->options(TaxScope::class),
                        ToggleButton::make('enabled')
                            ->localizeLabel('Default')
                            ->onLabel(Tax::enabledLabel())
                            ->offLabel(Tax::disabledLabel()),
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
                    ->icon(static fn (Tax $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static function (Tax $record) {
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
                    ->rate(static fn (Tax $record) => $record->computation->value)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->localizeLabel()
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(static function (Tax $record) {
                return $record->isDisabled();
            })
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
