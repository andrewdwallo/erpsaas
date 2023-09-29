<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\CategoryType;
use App\Filament\Company\Resources\Setting\CategoryResource\Pages;
use App\Models\Setting\Category;
use Closure;
use Exception;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\{Forms, Tables};
use Illuminate\Database\Eloquent\Collection;
use Wallo\FilamentSelectify\Components\ToggleButton;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/categories';

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
                                    $existingCategory = Category::where('company_id', auth()->user()->currentCompany->id)
                                        ->where('name', $value)
                                        ->where('type', $get('type'))
                                        ->first();

                                    if ($existingCategory && $existingCategory->getKey() !== $component->getRecord()?->getKey()) {
                                        $type = ucwords($get('type'));
                                        $fail("The {$type} category \"{$value}\" already exists.");
                                    }
                                };
                            }),
                        Forms\Components\Select::make('type')
                            ->options(CategoryType::class)
                            ->required()
                            ->native(false)
                            ->label('Type'),
                        Forms\Components\ColorPicker::make('color')
                            ->required()
                            ->label('Color'),
                        ToggleButton::make('enabled')
                            ->label('Default'),
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
                    ->tooltip(static fn (Category $record) => $record->enabled ? "Default {$record->type->getLabel()} Category" : null)
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Color')
                    ->copyable()
                    ->copyMessage('Color code copied'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->options(CategoryType::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(static function (Category $record, Tables\Actions\DeleteAction $action) {
                        if ($record->enabled) {
                            Notification::make()
                                ->danger()
                                ->title('Action Denied')
                                ->body(__('The :name category is currently set as your default :type category and cannot be deleted. Please set a different category as your default before attempting to delete this one.', ['name' => $record->name, 'type' => $record->type->getLabel()]))
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
                            $defaultCategories = $records->filter(static function (Category $record) {
                                return $record->enabled;
                            });

                            if ($defaultCategories->isNotEmpty()) {
                                $defaultCategoryNames = $defaultCategories->pluck('name')->toArray();

                                Notification::make()
                                    ->danger()
                                    ->title('Action Denied')
                                    ->body(static function () use ($defaultCategoryNames) {
                                        $message = __('The following categories are currently set as your default and cannot be deleted. Please set a different category as your default before attempting to delete these ones.') . '<br><br>';
                                        $message .= implode('<br>', array_map(static function ($name) {
                                            return '&bull; ' . $name;
                                        }, $defaultCategoryNames));

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
