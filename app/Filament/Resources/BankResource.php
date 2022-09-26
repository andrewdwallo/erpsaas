<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankResource\Pages;
use App\Filament\Resources\BankResource\RelationManagers;
use App\Models\Bank;
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
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->nullable(),
                Forms\Components\Select::make('department_id')->relationship('department', 'name')->nullable(),
                Forms\Components\TextInput::make('bank_type')->maxLength(255)->label('Bank Type'),
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
                Tables\Columns\TextColumn::make('transactions_count')->counts('transactions')->label('Transactions'),
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
