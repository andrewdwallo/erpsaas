<?php

namespace App\Filament\Pages\CompanyWidgets;

use App\Models\Company;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Companies extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Company::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('user.name', 'name')->label('Owner'),
            Tables\Columns\ImageColumn::make('logo')->size(40),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('website'),
            Tables\Columns\TextColumn::make('address'),
            Tables\Columns\TextColumn::make('departments_count')->counts('departments')->label('Departments'),
            Tables\Columns\TextColumn::make('employees_count')->counts('employees')->label('Employees'),
            Tables\Columns\TextColumn::make('banks_count')->counts('banks')->label('Banks'),
            Tables\Columns\TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                ->form([
                    Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->label('Owner'),
                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Company $record): ?Company => $record)->maxLength(250),
                    Forms\Components\TextInput::make('website')->prefix('https://')->maxLength(250),
                    Forms\Components\TextInput::make('address')->maxLength(250),
                    Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->label('Owner'),
                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Company $record): ?Company => $record)->maxLength(250),
                    Forms\Components\TextInput::make('website')->prefix('https://')->maxLength(250),
                    Forms\Components\TextInput::make('address')->maxLength(250),
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
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->label('Owner'),
                Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Company $record): ?Company => $record)->maxLength(250),
                Forms\Components\TextInput::make('website')->prefix('https://')->maxLength(250),
                Forms\Components\TextInput::make('address')->maxLength(250),
                Forms\Components\FileUpload::make('logo')->image()->directory('logos'),
            ]),
        ];
    }
}
