<?php

namespace App\Filament\Pages\AccountWidgets;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Department;
use Filament\Forms;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Accounts extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Account::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\TextColumn::make('department.name', 'name'),
            Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
            Tables\Columns\TextColumn::make('account_type')->label('Account Type'),
            Tables\Columns\TextColumn::make('account_name')->label('Account Name'),
            Tables\Columns\TextColumn::make('account_number')->label('Account Number'),
            Tables\Columns\TextColumn::make('currency'),
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
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                    Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(function (callable $get) {
                        $company = Company::find($get('company_id'));

                        if (! $company) {
                            return Department::all()->pluck('name', 'id');
                        }

                        return $company->departments->pluck('name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                    Forms\Components\Select::make('bank_id')
                    ->label('Bank Name')
                    ->options(function (callable $get) {
                        $department = Department::find($get('department_id'));

                        if (! $department) {
                            return Bank::all()->pluck('bank_name', 'id');
                        }

                        return $department->banks->pluck('bank_name', 'id');
                    }),

                    Forms\Components\Select::make('account_type')->label('Account Type')
                    ->options([
                        'Checking' => 'Checking',
                        'Savings' => 'Savings',
                        'Money Market' => 'Money Market',
                        'Certificate of Deposit' => 'Certificate of Deposit',
                    ]),
                    Forms\Components\TextInput::make('account_name')->maxLength(255)->label('Account Name'),
                    Forms\Components\TextInput::make('account_number')->maxLength(255)->label('Account Number'),
                    Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                    ]),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                    Forms\Components\Select::make('department_id')
                    ->label('Department')
                    ->options(function (callable $get) {
                        $company = Company::find($get('company_id'));

                        if (! $company) {
                            return Department::all()->pluck('name', 'id');
                        }

                        return $company->departments->pluck('name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                    Forms\Components\Select::make('bank_id')
                    ->label('Bank Name')
                    ->options(function (callable $get) {
                        $department = Department::find($get('department_id'));

                        if (! $department) {
                            return Bank::all()->pluck('bank_name', 'id');
                        }

                        return $department->banks->pluck('bank_name', 'id');
                    }),

                    Forms\Components\Select::make('account_type')->label('Account Type')
                    ->options([
                        'Checking' => 'Checking',
                        'Savings' => 'Savings',
                        'Money Market' => 'Money Market',
                        'Certificate of Deposit' => 'Certificate of Deposit',
                    ]),
                    Forms\Components\TextInput::make('account_name')->maxLength(255)->label('Account Name'),
                    Forms\Components\TextInput::make('account_number')->maxLength(255)->label('Account Number'),
                    Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                    ]),
                ]),
            ]),
        ];
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
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                Forms\Components\Select::make('department_id')
                ->label('Department')
                ->options(function (callable $get) {
                    $company = Company::find($get('company_id'));

                    if (! $company) {
                        return Department::all()->pluck('name', 'id');
                    }

                    return $company->departments->pluck('name', 'id');
                })
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null)),

                Forms\Components\Select::make('bank_id')
                ->label('Bank Name')
                ->options(function (callable $get) {
                    $department = Department::find($get('department_id'));

                    if (! $department) {
                        return Bank::all()->pluck('bank_name', 'id');
                    }

                    return $department->banks->pluck('bank_name', 'id');
                }),

                Forms\Components\Select::make('account_type')->label('Account Type')
                ->options([
                    'Checking' => 'Checking',
                    'Savings' => 'Savings',
                    'Money Market' => 'Money Market',
                    'Certificate of Deposit' => 'Certificate of Deposit',
                ]),
                Forms\Components\TextInput::make('account_name')->maxLength(255)->label('Account Name'),
                Forms\Components\TextInput::make('account_number')->maxLength(255)->label('Account Number'),
                Forms\Components\Select::make('currency')
                ->options([
                    'USD' => 'USD',
                ]),
            ]),
        ];
    }
}
