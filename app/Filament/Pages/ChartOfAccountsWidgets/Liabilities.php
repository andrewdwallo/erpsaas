<?php

namespace App\Filament\Pages\ChartOfAccountsWidgets;

use App\Models\Company;
use App\Models\Department;
use App\Models\Liability;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Liabilities extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Liability::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name')->hidden(),
            Tables\Columns\TextColumn::make('department.name', 'name')->hidden(),
            Tables\Columns\TextColumn::make('code'),
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\TextColumn::make('description')->hidden(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null)),

                    Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(function (callable $get) {
                        $company = Company::find($get('company_id'));

                        if (! $company) {
                            return Department::all()->pluck('name', 'id');
                        }

                        return $company->departments->pluck('name', 'id');
                    }),

                    Forms\Components\TextInput::make('code')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('type')
                        ->required()
                        ->options([
                            'Current Liabilities' => 'Current Liabilities',
                            'Noncurrent Liabilities' => 'Noncurrent Liabilities',
                        ]),
                    Forms\Components\TextInput::make('description')
                        ->maxLength(255),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null)),

                    Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(function (callable $get) {
                        $company = Company::find($get('company_id'));

                        if (! $company) {
                            return Department::all()->pluck('name', 'id');
                        }

                        return $company->departments->pluck('name', 'id');
                    }),

                    Forms\Components\TextInput::make('code')->required()->unique()->numeric()->minValue(200)->maxValue(299),
                    Forms\Components\TextInput::make('name')->required()->maxLength(50)->unique(),
                    Forms\Components\Select::make('type')
                        ->required()
                        ->options([
                            'Current Liabilities' => 'Current Liabilities',
                            'Noncurrent Liabilities' => 'Noncurrent Liabilities',
                        ]),
                    Forms\Components\TextInput::make('description')
                        ->maxLength(255),
                ]),
            ]),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()
            ->form([
                Forms\Components\Select::make('company_id')
                ->label('Company')
                ->options(Company::all()->pluck('name', 'id')->toArray())
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null)),

                Forms\Components\Select::make('department_id')
                ->label('Department')
                ->options(function (callable $get) {
                    $company = Company::find($get('company_id'));

                    if (! $company) {
                        return Department::all()->pluck('name', 'id');
                    }

                    return $company->departments->pluck('name', 'id');
                }),

                Forms\Components\TextInput::make('code')->required()->unique()->numeric()->minValue(200)->maxValue(299),
                Forms\Components\TextInput::make('name')->required()->maxLength(50)->unique(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'Current Liabilities' => 'Current Liabilities',
                        'Noncurrent Liabilities' => 'Noncurrent Liabilities',
                    ]),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
            ]),
        ];
    }
}
