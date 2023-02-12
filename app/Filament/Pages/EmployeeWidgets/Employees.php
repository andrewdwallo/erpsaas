<?php

namespace App\Filament\Pages\EmployeeWidgets;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Database\Eloquent\Builder;

class Employees extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Employee::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name'),
            Tables\Columns\TextColumn::make('department.name', 'name'),
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\ImageColumn::make('photo')->size(40),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('phone')->formatStateUsing(fn ($record) => ($record->phone != '') ? vsprintf('(%d%d%d) %d%d%d-%d%d%d%d', str_split($record->phone)) : '-'),
            Tables\Columns\TextColumn::make('address'),
            Tables\Columns\BooleanColumn::make('active')->trueIcon('heroicon-o-badge-check')->falseIcon('heroicon-o-x-circle'),
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

                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\FileUpload::make('photo')->image()->directory('photos')->visibility('private'),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Employee $record): ?Employee => $record)->maxLength(250),
                    Forms\Components\TextInput::make('phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                    Forms\Components\TextInput::make('address')->maxLength(250),
                    Forms\Components\Toggle::make('active')->default(true),
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

                    Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                    Forms\Components\FileUpload::make('photo')->image()->directory('photos')->visibility('private'),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Employee $record): ?Employee => $record)->maxLength(250),
                    Forms\Components\TextInput::make('phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                    Forms\Components\TextInput::make('address')->maxLength(250),
                    Forms\Components\Toggle::make('active')->default(true),
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

                Forms\Components\TextInput::make('name')->required()->maxLength(100)->autofocus(),
                Forms\Components\FileUpload::make('photo')->image()->directory('photos')->visibility('private'),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignorable: fn (?Employee $record): ?Employee => $record)->maxLength(250),
                Forms\Components\TextInput::make('phone')->mask(fn (TextInput\Mask $mask) => $mask->pattern('(000) 000-0000')),
                Forms\Components\TextInput::make('address')->maxLength(250),
                Forms\Components\Toggle::make('active')->default(true),
            ]),
        ];
    }
}
