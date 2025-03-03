<?php

namespace App\Filament\Company\Resources\Core\DepartmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->localizeLabel()
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('manager_id')
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
                Forms\Components\MarkdownEditor::make('description')->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel(translate('Department'))
            ->inverseRelationship('parent')
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $existingChildren = $this->getRelationship()->pluck('id')->toArray();

                        return $query->whereNotIn('id', $existingChildren)
                            ->whereNotNull('parent_id');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
