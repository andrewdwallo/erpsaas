<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
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
use Filament\Tables\Filters\MultiSelectFilter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationGroup = 'Resource Management';
    protected static ?int $navigationSort = 7;

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
                ->afterStateUpdated(fn (callable $set) => $set('card_id', null)),

                Forms\Components\Select::make('card_id')
                ->label('Card Name')
                ->options(function (callable $get) {
                    $account = Account::find($get('account_id'));

                    if (! $account) {
                        return Card::all()->pluck('card_name', 'id');
                    }

                    return $account->cards->pluck('card_name', 'id');
                }),

                Forms\Components\TextInput::make('date')->nullable(),
                Forms\Components\TextInput::make('merchant_name')->nullable(),
                Forms\Components\TextInput::make('description')->maxLength(255),
                Forms\Components\TextInput::make('amount')->maxLength(255),
                Forms\Components\TextInput::make('running_balance')->maxLength(255),
                Forms\Components\TextInput::make('available_balance')->maxLength(255),
                Forms\Components\TextInput::make('debit_amount')->maxLength(255),
                Forms\Components\TextInput::make('credit_amount')->maxLength(255),
                Forms\Components\TextInput::make('category')->maxLength(255),
                Forms\Components\TextInput::make('check_number')->maxLength(255),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name', 'name'),
                Tables\Columns\TextColumn::make('department.name', 'name'),
                Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
                Tables\Columns\TextColumn::make('account.account_name', 'account_name')->label('Account Name'),
                Tables\Columns\TextColumn::make('card.card_name', 'card_name')->label('Card Name'),
                Tables\Columns\TextColumn::make('date'),
                Tables\Columns\TextColumn::make('merchant_name'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('amount')->money('USD', 2),
                Tables\Columns\TextColumn::make('running_balance')->money('USD', 2),
                Tables\Columns\TextColumn::make('available_balance')->money('USD', 2),
                Tables\Columns\TextColumn::make('debit_amount')->money('USD', 2),
                Tables\Columns\TextColumn::make('credit_amount')->money('USD', 2),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('check_number'),
            ])
            ->filters([
                MultiSelectFilter::make('company.name', 'name'),
                MultiSelectFilter::make('department.name', 'name'),
                MultiSelectFilter::make('bank.bank_name', 'bank_name')->label('Bank Name'),
                MultiSelectFilter::make('account.account_name', 'account_name')->label('Account Name'),
                MultiSelectFilter::make('card.card_name', 'card_name')->label('Card Name'),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }    
}
