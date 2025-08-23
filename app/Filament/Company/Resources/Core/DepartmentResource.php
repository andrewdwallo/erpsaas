<?php

namespace App\Filament\Company\Resources\Core;

use App\Filament\Company\Resources\Core\DepartmentResource\Pages\CreateDepartment;
use App\Filament\Company\Resources\Core\DepartmentResource\Pages\EditDepartment;
use App\Filament\Company\Resources\Core\DepartmentResource\Pages\ListDepartments;
use App\Filament\Company\Resources\Core\DepartmentResource\RelationManagers\ChildrenRelationManager;
use App\Models\Core\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $modelLabel = 'Department';

    protected static ?string $slug = 'hr/departments';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('name')
                            ->autofocus()
                            ->required()
                            ->localizeLabel()
                            ->maxLength(100),
                        Select::make('manager_id')
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
                        Group::make()
                            ->schema([
                                Select::make('parent_id')
                                    ->localizeLabel('Parent department')
                                    ->relationship('parent', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->nullable(),
                                Textarea::make('description')
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
                TextColumn::make('name')
                    ->localizeLabel()
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('manager.name')
                    ->localizeLabel()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('children_count')
                    ->localizeLabel('Children')
                    ->badge()
                    ->counts('children')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }
}
