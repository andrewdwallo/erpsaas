<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseTransactionResource\Pages;
use App\Filament\Resources\ExpenseTransactionResource\RelationManagers;
use App\Models\ExpenseTransaction;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use App\Models\Department;
use App\Models\Company;
use App\Models\Bank;
use App\Models\Account;
use App\Models\Card;
use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\MultiSelectFilter;
use Filament\Tables\Columns\TextColumn;
use App\Models\Expense;
use App\Models\Revenue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseTransactionResource extends Resource
{
    protected static ?string $model = ExpenseTransaction::class;

    protected static ?string $modelLabel = 'Expenses';

    protected static ?string $navigationGroup = 'Bank';
    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                Forms\Components\DatePicker::make('date')->maxDate(now())->format('m/d/Y')->displayFormat('m/d/Y'),
                Forms\Components\TextInput::make('number')->nullable()->numeric()->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: 'TRA-0000', thousandsSeparator: '', decimalPlaces:0, isSigned: false))->label('Transaction Number'),
                Forms\Components\Select::make('expense_id')->label('Expense Account')
                ->options(Expense::all()->pluck('name', 'id')->toArray()),
                Forms\Components\TextInput::make('merchant_name')->nullable()->label('Merchant Name'),
                Forms\Components\TextInput::make('description')->maxLength(255)->label('Transaction Description'),
                Forms\Components\TextInput::make('amount')->mask(fn (TextInput\Mask $mask) => $mask->money(prefix: '$', thousandsSeparator: ',', decimalPlaces: 2, isSigned: false))

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name', 'name'),
                Tables\Columns\TextColumn::make('department.name', 'name'),
                Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
                Tables\Columns\TextColumn::make('account.account_name', 'account_name')->label('Bank Account Name'),
                Tables\Columns\TextColumn::make('card.card_name', 'card_name')->label('Card Network'),
                Tables\Columns\TextColumn::make('date'),
                Tables\Columns\TextColumn::make('number'),
                Tables\Columns\TextColumn::make('expense.name', 'name')->label('Account Name'),
                Tables\Columns\TextColumn::make('merchant_name')->label('Merchant Name'),
                Tables\Columns\TextColumn::make('description')->hidden(),
                Tables\Columns\TextColumn::make('amount')->money('USD', 2),
            ])
            ->filters([
                MultiSelectFilter::make('company.name', 'name'),
                MultiSelectFilter::make('department.name', 'name'),
                MultiSelectFilter::make('bank.bank_name', 'bank_name')->label('Bank Name'),
                MultiSelectFilter::make('account.account_name', 'account_name')->label('Account Name'),
                MultiSelectFilter::make('card.card_name', 'card_name')->label('Card Network'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseTransactions::route('/'),
            'create' => Pages\CreateExpenseTransaction::route('/create'),
            'view' => Pages\ViewExpenseTransaction::route('/{record}'),
            'edit' => Pages\EditExpenseTransaction::route('/{record}/edit'),
        ];
    }    
}
