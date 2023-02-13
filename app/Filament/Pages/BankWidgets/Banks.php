<?php

namespace App\Filament\Pages\BankWidgets;

use App\Models\Asset;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Banks extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Bank::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\TextColumn::make('department.name', 'name'),
            Tables\Columns\TextColumn::make('bank_type')->label('Bank Type'),
            Tables\Columns\TextColumn::make('bank_name')->label('Bank Name'),
            Tables\Columns\TextColumn::make('bank_phone')->formatStateUsing(fn ($record) => vsprintf('(%d%d%d) %d%d%d-%d%d%d%d', str_split($record->bank_phone))),
            Tables\Columns\TextColumn::make('bank_address')->label('Address'),
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
                    Forms\Components\Select::make('bank_type')->label('Bank Type')
                    ->options([
                        'Retail Bank' => 'Retail Bank',
                        'Commercial Bank' => 'Commercial Bank',
                        'Investment Bank' => 'Investment Bank',
                        'Credit Union' => 'Credit Union',
                        'Private Bank' => 'Private Bank',
                        'Online Bank' => 'Online Bank',
                        'Savings & Loan Bank' => 'Savings & Loan Bank',
                        'Shadow Bank' => 'Shadow Bank',
                        'Neobank' => 'Neobank',
                        'Challenger Bank' => 'Challenger Bank',
                    ]),

                    Forms\Components\Select::make('bank_name')
                    ->label('Bank Account Name')
                    ->options(Asset::all()->pluck('name', 'name')->toArray()),

                    Forms\Components\TextInput::make('bank_phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                    Forms\Components\TextInput::make('bank_address')->maxLength(255)->label('Address'),
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
                    Forms\Components\Select::make('bank_type')->label('Bank Type')
                    ->options([
                        'Retail Bank' => 'Retail Bank',
                        'Commercial Bank' => 'Commercial Bank',
                        'Investment Bank' => 'Investment Bank',
                        'Credit Union' => 'Credit Union',
                        'Private Bank' => 'Private Bank',
                        'Online Bank' => 'Online Bank',
                        'Savings & Loan Bank' => 'Savings & Loan Bank',
                        'Shadow Bank' => 'Shadow Bank',
                        'Neobank' => 'Neobank',
                        'Challenger Bank' => 'Challenger Bank',
                    ]),

                    Forms\Components\Select::make('bank_name')
                    ->label('Bank Account Name')
                    ->options(Asset::all()->pluck('name', 'name')->toArray()),

                    Forms\Components\TextInput::make('bank_phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                    Forms\Components\TextInput::make('bank_address')->maxLength(255)->label('Address'),
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
                Forms\Components\Select::make('bank_type')->label('Bank Type')
                ->options([
                    'Retail Bank' => 'Retail Bank',
                    'Commercial Bank' => 'Commercial Bank',
                    'Investment Bank' => 'Investment Bank',
                    'Credit Union' => 'Credit Union',
                    'Private Bank' => 'Private Bank',
                    'Online Bank' => 'Online Bank',
                    'Savings & Loan Bank' => 'Savings & Loan Bank',
                    'Shadow Bank' => 'Shadow Bank',
                    'Neobank' => 'Neobank',
                    'Challenger Bank' => 'Challenger Bank',
                ]),

                Forms\Components\Select::make('bank_name')
                ->label('Bank Account Name')
                ->options(Asset::all()->pluck('name', 'name')->toArray()),

                Forms\Components\TextInput::make('bank_phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                Forms\Components\TextInput::make('bank_address')->maxLength(255)->label('Address'),
            ]),
        ];
    }
}
