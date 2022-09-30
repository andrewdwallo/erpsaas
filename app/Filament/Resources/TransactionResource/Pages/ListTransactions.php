<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Forms\Components\Actions\Modal\Actions\Action;
use Filament\Pages\Actions;
use App\Models\Company;
use App\Models\Department;
use App\Models\Bank;
use App\Models\Account;
use App\Models\Card;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\ImportField;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Konnco\FilamentImport\Actions\ImportField as ActionsImportField;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ImportAction::make()
            ->fields([
                Select::make('company_id')
                ->label('Company')
                ->options(Company::all()->pluck('name', 'id')->toArray())
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null))
                ->required()
                ->helperText('The Name of The Company'),
                Select::make('department_id')
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
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null))
                ->required()
                ->helperText('The Name of The Department'),
                Select::make('bank_id')
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
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null))
                ->required()
                ->helperText('The Name of The Bank'),
                Select::make('account_id')
                ->label('Account Name')
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
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null))
                ->required()
                ->helperText('The Name of The Account'),
                Select::make('card_id')
                ->label('Card Name')
                ->options(function (callable $get) {
                    $account = Account::find($get('account_id'));

                    if (! $account) {
                        return Card::all()->pluck('card_name', 'id');
                    }

                    return $account->cards->pluck('card_name', 'id');
                })
                ->required()
                ->helperText('The Name of The Card'),
                ActionsImportField::make('date')
                ->label('Transaction Date')
                ->helperText('The Date of the Transaction'),
                ActionsImportField::make('description')
                ->label('Transaction Description')
                ->helperText('The Description Given by Your Bank'),
                ActionsImportField::make('amount')
                ->label('Transaction Amount in Total with debit and credut amount')
                ->helperText('The Amount of The Transaction'),
                ActionsImportField::make('running_balance')
                ->label('Running Balance')
                ->helperText('The Running Balance Amount of Your Account During The Transaction aka Available Balance'),
            ])

        ];
    }
}
