<?php

namespace App\Filament\Company\Resources\Core;

use App\Filament\Company\Resources\Core\DepartmentResource\Pages;
use App\Filament\Company\Resources\Core\DepartmentResource\RelationManagers\ChildrenRelationManager;
use App\Models\Core\Department;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $modelLabel = 'Department';

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $navigationGroup = 'HR';

    protected static ?string $slug = 'hr/departments';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return 'HR';
        }

        return null;
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
                            ->maxLength(100),
                        Forms\Components\Select::make('manager_id')
                            ->relationship(
                                name: 'manager',
                                titleAttribute: 'name',
                                modifyQueryUsing: static function (Builder $query) {
                                    $company = auth()->user()->currentCompany;
                                    $companyUsers = $company->allUsers()->pluck('id')->toArray();

                                    return $query->whereIn('id', $companyUsers);
                                }
                            )
                            ->localizeLabel()
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Select::make('parent_id')
                                    ->localizeLabel('Parent Department')
                                    ->relationship('parent', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->nullable(),
                                Forms\Components\Textarea::make('description')
                                    ->autosize()
                                    ->nullable()
                                    ->localizeLabel(),
                            ])->columns(1),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel()
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('children_count')
                    ->localizeLabel('Children')
                    ->badge()
                    ->counts('children')
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
