<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\TaxComputation;
use App\Enums\TaxScope;
use App\Enums\TaxType;
use App\Filament\Company\Resources\Setting\TaxResource\Pages;
use App\Filament\Company\Resources\Setting\TaxResource\RelationManagers;
use App\Models\Setting\Category;
use App\Models\Setting\Tax;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Wallo\FilamentSelectify\Components\ToggleButton;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/taxes';

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
                                    $existingCategory = Tax::where('company_id', auth()->user()->currentCompany->id)
                                                                ->where('name', $value)
                                                                ->where('type', $get('type'))
                                                                ->first();

                                    if ($existingCategory && $existingCategory->getKey() !== $component->getRecord()?->getKey()) {
                                        $type = $get('type')->getLabel();
                                        $fail("The {$type} tax \"{$value}\" already exists.");
                                    }
                                };
                            }),
                        Forms\Components\TextInput::make('description')
                            ->label('Description'),
                        Forms\Components\Select::make('computation')
                            ->label('Computation')
                            ->options(TaxComputation::class)
                            ->default(TaxComputation::Percentage)
                            ->live()
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->label('Rate')
                            ->numeric()
                            ->suffix(static function (Forms\Get $get) {
                                $computation = $get('computation');

                                if ($computation === TaxComputation::Percentage) {
                                    return '%';
                                }

                                return null;
                            })
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(TaxType::class)
                            ->default(TaxType::Sales)
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->label('Scope')
                            ->options(TaxScope::class)
                            ->native(false),
                        ToggleButton::make('enabled')
                            ->label('Enabled'),
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
                    ->icon(static fn (Tax $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Tax $record) => $record->enabled ? "Default {$record->type->getLabel()} Tax" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation')
                    ->label('Computation')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->formatStateUsing(static fn (Tax $record) => $record->rate . ($record->computation === TaxComputation::Percentage ? '%' : null))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(static function (Tables\Actions\DeleteAction $action, Tax $record) {
                        if ($record->enabled) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name tax is currently set as your default :type tax and cannot be deleted. Please set a different tax as your default before attempting to delete this one.', ['name' => $record->name, 'type' => $record->type->getLabel()]))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(static function (Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $defaultTaxes = $records->filter(static function (Tax $record) {
                                return $record->enabled;
                            });

                            if ($defaultTaxes->isNotEmpty()) {
                                $defaultTaxNames = $defaultTaxes->pluck('name')->toArray();

                                Notification::make()
                                    ->danger()
                                    ->title('Action Denied')
                                    ->body(static function () use ($defaultTaxNames) {
                                        $message = __('The following taxes are currently set as your default and cannot be deleted. Please set a different tax as your default before attempting to delete these ones.') . "<br><br>";
                                        $message .= implode("<br>", array_map(static function ($name) {
                                            return "&bull; " . $name;
                                        }, $defaultTaxNames));
                                        return $message;
                                    })
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
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
            'index' => Pages\ListTaxes::route('/'),
            'create' => Pages\CreateTax::route('/create'),
            'edit' => Pages\EditTax::route('/{record}/edit'),
        ];
    }
}
