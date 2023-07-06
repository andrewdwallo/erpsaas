<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use Wallo\FilamentSelectify\Components\ToggleButton;
use App\Models\Setting\Category;
use Exception;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Collection;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Color')
                            ->default('#4f46e5')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Category::getCategoryTypes())
                            ->searchable()
                            ->required(),
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
                    ->icon(static fn (Category $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Category $record) => $record->enabled ? "Default " .ucwords($record->type) . " Category" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(static fn (Category $record): string => ucwords($record->type))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color')
                    ->copyable()
                    ->copyMessage('Color copied to clipboard.'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(static function (Category $record, Tables\Actions\DeleteAction $action) {
                        if ($record->enabled) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name category is currently set as your default :Type category and cannot be deleted. Please set a different category as your default before attempting to delete this one.', ['name' => $record->name, 'Type' => ucwords($record->type)]))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(static function (Collection $records, Tables\Actions\DeleteBulkAction $action) {
                        $defaultCategories = $records->filter(static function (Category $record) {
                            return $record->enabled;
                        });

                        if ($defaultCategories->isNotEmpty()) {
                            $defaultCategoryNames = $defaultCategories->pluck('name')->toArray();

                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(static function () use ($defaultCategoryNames) {
                                    $message = __('The following categories are currently set as your default and cannot be deleted. Please set a different category as your default before attempting to delete these ones.') . "<br><br>";
                                    $message .= implode("<br>", array_map(static function ($name) {
                                        return "&bull; " . $name;
                                    }, $defaultCategoryNames));
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
