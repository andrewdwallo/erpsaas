<?php

namespace App\Filament\Pages\DepartmentWidgets;

use App\Models\Department;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Departments extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Department::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\ImageColumn::make('logo')->size(40),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('employees_count')->counts('employees')->label('Employees'),
            Tables\Columns\TextColumn::make('banks_count')->counts('banks')->label('Banks'),
            Tables\Columns\TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
            Tables\Columns\TextColumn::make('cards_count')->counts('cards')->label('Cards'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')->relationship('company', 'name')->required(),
                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')->relationship('company', 'name')->required(),
                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
                ]),
            ]),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
            ->form([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->required(),
                Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
            ]),
        ];
    }
}
