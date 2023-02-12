<?php

namespace App\Filament\Pages\ChartOfAccountsWidgets;

use App\Models\Company;
use App\Models\Department;
use App\Models\Expense;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Expenses extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Expense::query();
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
            Tables\Columns\TextColumn::make('expense_transactions_sum_amount')->sum('expense_transactions', 'amount')->money('USD', 2)->label('Amount'),
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
                            'Direct Costs' => 'Direct Costs',
                            'Expense' => 'Expense',
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

                    Forms\Components\TextInput::make('code')->required()->unique()->numeric()->minValue(500)->maxValue(599),
                    Forms\Components\TextInput::make('name')->required()->maxLength(50)->unique(),
                    Forms\Components\Select::make('type')
                        ->required()
                        ->options([
                            'Direct Costs' => 'Direct Costs',
                            'Expense' => 'Expense',
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

                Forms\Components\TextInput::make('code')->required()->unique()->numeric()->minValue(500)->maxValue(599),
                Forms\Components\TextInput::make('name')->required()->maxLength(50)->unique(),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'Direct Costs' => 'Direct Costs',
                        'Expense' => 'Expense',
                    ]),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
            ]),
        ];
    }
}
