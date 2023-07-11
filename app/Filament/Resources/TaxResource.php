<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxResource\Pages;
use App\Filament\Resources\TaxResource\RelationManagers;
use App\Models\Setting\Tax;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentSelectify\Components\ToggleButton;

class TaxResource extends Resource
{
    protected static ?string $model = Tax::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-tax';

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
                            ->options(Tax::getComputationTypes())
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
                            ->suffix(static function (callable $get) {
                                $computation = $get('computation');

                                if ($computation === 'percentage' || $computation === 'compound') {
                                    return '%';
                                }

                                return null;
                            })
                            ->default(0.0000)
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Tax::getTaxTypes())
                            ->searchable()
                            ->default('sales')
                            ->required(),
                        Forms\Components\Select::make('scope')
                            ->label('Scope')
                            ->options(Tax::getTaxScopes())
                            ->searchable(),
                        ToggleButton::make('enabled')
                            ->label('Default')
                            ->offColor('danger')
                            ->onColor('primary'),
                    ])->columns(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->weight('semibold')
                    ->icon(static fn (Tax $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Tax $record) => $record->enabled ? "Default " .ucwords($record->type) . " Tax" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('computation')
                    ->label('Computation')
                    ->formatStateUsing(static fn (Tax $record) => ucwords($record->computation))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate')
                    ->formatStateUsing(static function (Tax $record) {
                        $rate = $record->rate;

                        return $rate . ($record->computation === 'percentage' || $record->computation === 'compound' ? '%' : null);
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(static fn (Tax $record) => ucwords($record->type))
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
                                ->body(__('The :name tax is currently set as your default :Type tax and cannot be deleted. Please set a different tax as your default before attempting to delete this one.', ['name' => $record->name, 'Type' => ucwords($record->type)]))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
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
