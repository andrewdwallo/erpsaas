<?php

namespace App\Filament\Company\Resources\Core\DepartmentResource\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->localizeLabel()
                    ->required()
                    ->maxLength(100),
                Select::make('manager_id')
                    ->localizeLabel()
                    ->relationship(
                        name: 'manager',
                        titleAttribute: 'name',
                        modifyQueryUsing: static function (Builder $query) {
                            $company = auth()->user()->currentCompany;
                            $companyUsers = $company->allUsers()->pluck('id')->toArray();

                            return $query->whereIn('id', $companyUsers);
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->nullable(),
                MarkdownEditor::make('description')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(translate('Department'))
            ->inverseRelationship('parent')
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $existingChildren = $this->getRelationship()->pluck('id')->toArray();

                        return $query->whereNotIn('id', $existingChildren)
                            ->whereNotNull('parent_id');
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
