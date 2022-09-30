<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Filament\Resources\BankResource\RelationManagers;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Company;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationGroup = 'Resource Management';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                Forms\Components\TextInput::make('bank_name')->maxLength(255)->label('Bank Name'),
                Forms\Components\TextInput::make('bank_phone')->tel()->maxLength(255)->label('Phone Number'),
                Forms\Components\TextInput::make('bank_address')->maxLength(255)->label('Address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name', 'name'),
                Tables\Columns\TextColumn::make('department.name', 'name'),
                Tables\Columns\TextColumn::make('bank_type')->label('Bank Type'),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank Name'),
                Tables\Columns\TextColumn::make('bank_phone')->label('Phone Number'),
                Tables\Columns\TextColumn::make('bank_address')->label('Address'),
                Tables\Columns\TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
                Tables\Columns\TextColumn::make('cards_count')->counts('cards')->label('Cards'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListBanks::route('/'),
            'create' => Pages\CreateBank::route('/create'),
            'view' => Pages\ViewBank::route('/{record}'),
            'edit' => Pages\EditBank::route('/{record}/edit'),
        ];
    }    
}
