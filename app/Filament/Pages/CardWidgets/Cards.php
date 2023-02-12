<?php

namespace App\Filament\Pages\CardWidgets;

use App\Models\Account;
use App\Models\Bank;
use App\Models\Card;
use App\Models\Company;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Cards extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Card::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\TextColumn::make('department.name', 'name'),
            Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
            Tables\Columns\TextColumn::make('account.account_name', 'account_name')->label('Account Name'),
            Tables\Columns\TextColumn::make('card_name')->label('Card Name'),
            Tables\Columns\TextColumn::make('card_number')->label('Card Number'),
            Tables\Columns\TextColumn::make('name_on_card')->label('Name On Card'),
            Tables\Columns\TextColumn::make('expiration_date')->formatStateUsing(fn ($record) => vsprintf('%d%d/%d%d%d%d', str_split($record->expiration_date)))->label('Expiration Date'),
            Tables\Columns\TextColumn::make('security_code')->label('CVV'),
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
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                    Forms\Components\Select::make('account_id')
                    ->label('Account Name')
                    ->options(function (callable $get) {
                        $bank = Bank::find($get('bank_id'));

                        if (! $bank) {
                            return Account::all()->pluck('account_name', 'id');
                        }

                        return $bank->accounts->pluck('account_name', 'id');
                    }),

                    Forms\Components\TextInput::make('card_name')->placeholder('MasterCard, Visa, etc...')->label('Card Network'),
                    Forms\Components\TextInput::make('card_number')->nullable()->placeholder('1111 2222 3333 4444')->label('Card Number'),
                    Forms\Components\TextInput::make('name_on_card')->label('Name On Card'),
                    Forms\Components\TextInput::make('expiration_date')->mask(fn (TextInput\Mask $mask) => $mask->pattern('00/0000'))->placeholder('05/2025')->label('Expiration Date'),
                    Forms\Components\TextInput::make('security_code')->numeric()->mask(fn (TextInput\Mask $mask) => $mask->range()->from(100)->to(9999)->maxLength(4))->placeholder('123')->label('CVV'),
                ]),

                Tables\Actions\EditAction::make()
                ->form([
                    Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->options(Company::all()->pluck('name', 'id')->toArray())
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                    ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                    Forms\Components\Select::make('account_id')
                    ->label('Account Name')
                    ->options(function (callable $get) {
                        $bank = Bank::find($get('bank_id'));

                        if (! $bank) {
                            return Account::all()->pluck('account_name', 'id');
                        }

                        return $bank->accounts->pluck('account_name', 'id');
                    }),

                    Forms\Components\TextInput::make('card_name')->placeholder('MasterCard, Visa, etc...')->label('Card Network'),
                    Forms\Components\TextInput::make('card_number')->nullable()->placeholder('1111 2222 3333 4444')->label('Card Number'),
                    Forms\Components\TextInput::make('name_on_card')->label('Name On Card'),
                    Forms\Components\TextInput::make('expiration_date')->mask(fn (TextInput\Mask $mask) => $mask->pattern('00/0000'))->placeholder('05/2025')->label('Expiration Date'),
                    Forms\Components\TextInput::make('security_code')->numeric()->mask(fn (TextInput\Mask $mask) => $mask->range()->from(100)->to(9999)->maxLength(4))->placeholder('123')->label('CVV'),
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
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

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
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                Forms\Components\Select::make('account_id')
                ->label('Account Name')
                ->options(function (callable $get) {
                    $bank = Bank::find($get('bank_id'));

                    if (! $bank) {
                        return Account::all()->pluck('account_name', 'id');
                    }

                    return $bank->accounts->pluck('account_name', 'id');
                }),

                Forms\Components\TextInput::make('card_name')->placeholder('MasterCard, Visa, etc...')->label('Card Network'),
                Forms\Components\TextInput::make('card_number')->nullable()->placeholder('1111 2222 3333 4444')->label('Card Number'),
                Forms\Components\TextInput::make('name_on_card')->label('Name On Card'),
                Forms\Components\TextInput::make('expiration_date')->mask(fn (TextInput\Mask $mask) => $mask->pattern('00/0000'))->placeholder('05/2025')->label('Expiration Date'),
                Forms\Components\TextInput::make('security_code')->numeric()->mask(fn (TextInput\Mask $mask) => $mask->range()->from(100)->to(9999)->maxLength(4))->placeholder('123')->label('CVV'),
            ]),
        ];
    }
}
