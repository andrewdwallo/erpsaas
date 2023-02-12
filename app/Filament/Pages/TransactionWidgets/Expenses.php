<?php

namespace App\Filament\Pages\TransactionWidgets;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Company;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseTransaction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
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
        return ExpenseTransaction::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\TextColumn::make('department.name', 'name'),
            Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
            Tables\Columns\TextColumn::make('account.account_name', 'account_name')->label('Bank Account Name'),
            Tables\Columns\TextColumn::make('card.card_name', 'card_name')->label('Card Network'),
            Tables\Columns\TextColumn::make('paid_at')->label('Paid At'),
            Tables\Columns\TextColumn::make('number'),
            Tables\Columns\TextColumn::make('expense.name', 'name')->label('Account Name'),
            Tables\Columns\TextColumn::make('merchant_name')->label('Merchant Name'),
            Tables\Columns\TextColumn::make('description')->hidden(),
            Tables\Columns\TextColumn::make('amount')->money('USD', 2),
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
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('bank_id')
                    ->label('Bank Name')
                    ->options(function (callable $get) {
                        $department = Department::find($get('department_id'));

                        if (! $department) {
                            return Bank::all()->pluck('bank_name', 'id');
                        }

                        return $department->banks->pluck('bank_name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('account_id')
                    ->label('Bank Account Name')
                    ->options(function (callable $get) {
                        $bank = Bank::find($get('bank_id'));

                        if (! $bank) {
                            return Account::all()->pluck('account_name', 'id');
                        }

                        return $bank->accounts->pluck('account_name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('card_id')
                    ->label('Card Network')
                    ->options(function (callable $get) {
                        $account = Account::find($get('account_id'));

                        if (! $account) {
                            return Card::all()->pluck('card_name', 'id');
                        }

                        return $account->cards->pluck('card_name', 'id');
                    }),

                    Forms\Components\DatePicker::make('paid_at')->maxDate(now())->format('m/d/Y')->displayFormat('m/d/Y')->label('Paid At'),
                    Forms\Components\TextInput::make('number')->nullable()->numeric()->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: 'TRA-0000', thousandsSeparator: '', decimalPlaces:0, isSigned: false))->label('Transaction Number'),
                    Forms\Components\Select::make('expense_id')->label('Expense Account')
                    ->options(Expense::all()->pluck('name', 'id')->toArray()),
                    Forms\Components\TextInput::make('merchant_name')->nullable()->label('Merchant Name'),
                    Forms\Components\TextInput::make('description')->maxLength(255)->label('Transaction Description'),
                    Forms\Components\TextInput::make('amount')->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: '$', thousandsSeparator: ',', decimalPlaces: 2, isSigned: false)),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('bank_id')
                    ->label('Bank Name')
                    ->options(function (callable $get) {
                        $department = Department::find($get('department_id'));

                        if (! $department) {
                            return Bank::all()->pluck('bank_name', 'id');
                        }

                        return $department->banks->pluck('bank_name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('account_id')
                    ->label('Bank Account Name')
                    ->options(function (callable $get) {
                        $bank = Bank::find($get('bank_id'));

                        if (! $bank) {
                            return Account::all()->pluck('account_name', 'id');
                        }

                        return $bank->accounts->pluck('account_name', 'id');
                    })
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                    Forms\Components\Select::make('card_id')
                    ->label('Card Network')
                    ->options(function (callable $get) {
                        $account = Account::find($get('account_id'));

                        if (! $account) {
                            return Card::all()->pluck('card_name', 'id');
                        }

                        return $account->cards->pluck('card_name', 'id');
                    }),

                    Forms\Components\DatePicker::make('paid_at')->maxDate(now())->format('m/d/Y')->displayFormat('m/d/Y')->label('Paid At'),
                    Forms\Components\TextInput::make('number')->nullable()->numeric()->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: 'TRA-0000', thousandsSeparator: '', decimalPlaces:0, isSigned: false))->label('Transaction Number'),
                    Forms\Components\Select::make('expense_id')->label('Expense Account')
                    ->options(Expense::all()->pluck('name', 'id')->toArray()),
                    Forms\Components\TextInput::make('merchant_name')->nullable()->label('Merchant Name'),
                    Forms\Components\TextInput::make('description')->maxLength(255)->label('Transaction Description'),
                    Forms\Components\TextInput::make('amount')->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: '$', thousandsSeparator: ',', decimalPlaces: 2, isSigned: false)),
                ]),
            ]),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()->label('New Expense')
            ->form([
                Forms\Components\Select::make('company_id')
                ->label('Company')
                ->options(Company::all()->pluck('name', 'id')->toArray())
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

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
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                Forms\Components\Select::make('bank_id')
                ->label('Bank Name')
                ->options(function (callable $get) {
                    $department = Department::find($get('department_id'));

                    if (! $department) {
                        return Bank::all()->pluck('bank_name', 'id');
                    }

                    return $department->banks->pluck('bank_name', 'id');
                })
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                Forms\Components\Select::make('account_id')
                ->label('Bank Account Name')
                ->options(function (callable $get) {
                    $bank = Bank::find($get('bank_id'));

                    if (! $bank) {
                        return Account::all()->pluck('account_name', 'id');
                    }

                    return $bank->accounts->pluck('account_name', 'id');
                })
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                Forms\Components\Select::make('card_id')
                ->label('Card Network')
                ->options(function (callable $get) {
                    $account = Account::find($get('account_id'));

                    if (! $account) {
                        return Card::all()->pluck('card_name', 'id');
                    }

                    return $account->cards->pluck('card_name', 'id');
                }),

                Forms\Components\DatePicker::make('paid_at')->maxDate(now())->format('m/d/Y')->displayFormat('m/d/Y')->label('Paid At'),
                Forms\Components\TextInput::make('number')->nullable()->numeric()->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: 'TRA-0000', thousandsSeparator: '', decimalPlaces:0, isSigned: false))->label('Transaction Number'),
                Forms\Components\Select::make('expense_id')->label('Expense Account')
                ->options(Expense::all()->pluck('name', 'id')->toArray()),
                Forms\Components\TextInput::make('merchant_name')->nullable()->label('Merchant Name'),
                Forms\Components\TextInput::make('description')->maxLength(255)->label('Transaction Description'),
                Forms\Components\TextInput::make('amount')->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: '$', thousandsSeparator: ',', decimalPlaces: 2, isSigned: false)),
            ]),
        ];
    }
}
